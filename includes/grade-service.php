<?php
/** Lưu cả lớp trong một transaction, khóa hàng lớp để tránh lưu đồng thời. */
function luu_diem(PDO $pdo, array $user, int $lhpId, array $input, string $reason, bool $finalize, int $version): array
{
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT l.*, hk.trang_thai hk_trang_thai FROM lop_hoc_phan l JOIN hoc_ky hk ON hk.id=l.hoc_ky_id WHERE l.id=? FOR UPDATE');
        $st->execute([$lhpId]); $lhp = $st->fetch();
        if (!$lhp) throw new RuntimeException('Không tìm thấy lớp học phần.');
        $admin = $user['role'] === 'admin';
        if (!$admin) {
            $st = $pdo->prepare('SELECT id FROM giang_vien WHERE user_id=?'); $st->execute([$user['id']]);
            $gv = $st->fetchColumn();
            if ($user['role'] !== 'giangvien' || !$gv || (int)$gv !== (int)$lhp['giang_vien_id']) throw new RuntimeException('Bạn không được sửa điểm lớp này.');
            if ($lhp['diem_chinh_thuc']) throw new RuntimeException('Điểm đã lưu chính thức. Chỉ Admin được chỉnh sửa.');
            if ($lhp['hk_trang_thai'] === 'Đóng') throw new RuntimeException('Học kỳ đã đóng.');
        }
        if ($version !== (int)$lhp['phien_ban']) throw new RuntimeException('Điểm đã được thay đổi ở phiên khác. Tải lại trang trước khi lưu.');
        $st = $pdo->prepare('SELECT d.* FROM diem d JOIN dang_ky dk ON dk.id=d.dang_ky_id WHERE dk.lhp_id=? FOR UPDATE');
        $st->execute([$lhpId]); $old = [];
        foreach ($st as $r) $old[(int)$r['id']] = $r;
        if (!$old) throw new RuntimeException('Lớp chưa có sinh viên.');
        $new = $old;
        foreach ($input as $id => $values) {
            if (!ctype_digit((string)$id) || !isset($old[(int)$id]) || !is_array($values)) throw new RuntimeException('Dữ liệu không thuộc lớp học phần này.');
            $id = (int)$id;
            $raw = $values['cc'] ?? '';
            if (!is_string($raw) || strlen($raw)>20000) throw new RuntimeException('Điểm thường xuyên không hợp lệ.');
            $tx = trim($raw)==='' ? [] : array_map('parse_diem', explode(';', $raw));
            if (in_array(false,$tx,true) || in_array(null,$tx,true)) throw new RuntimeException('Mỗi điểm thường xuyên phải từ 0 đến 10, ngăn cách bằng dấu ;');
            $new[$id]['tx'] = json_encode($tx);
            $new[$id]['cc'] = $tx ? round(array_sum($tx)/count($tx),2) : null;
            foreach (['kt','thi'] as $c) {
                $v = parse_diem($values[$c] ?? '');
                if ($v === false) throw new RuntimeException('Giữa HP và cuối HP phải là một số từ 0 đến 10.');
                $new[$id][$c] = $v;
            }
        }
        if ($finalize || $lhp['diem_chinh_thuc']) foreach ($new as $d) {
            if (tinh_tong_ket($d,[]) === null) throw new RuntimeException('Chưa đủ điểm: cần ít nhất một bài thường xuyên, một điểm giữa HP và một điểm cuối HP cho mỗi sinh viên.');
        }
        $update = $pdo->prepare('UPDATE diem SET cc=?,tx=?,kt=?,thi=?,tong_ket=?,ket_qua=?,updated_by=?,updated_at=NOW() WHERE id=?');
        $log = $pdo->prepare('INSERT INTO lich_su_diem (diem_id,nguoi_sua,cot,gia_tri_cu,gia_tri_moi,ly_do,gia_tri_cu_text,gia_tri_moi_text) VALUES (?,?,?,?,?,?,?,?)');
        $changed = 0;
        foreach ($new as $id => $d) {
            $differences=[];
            foreach (COT_DIEM as $c => $_) {
                $before = $c==='cc' ? diem_thuong_xuyen($old[$id]) : ($old[$id][$c]===null ? null : (float)$old[$id][$c]);
                $after = $c==='cc' ? diem_thuong_xuyen($d) : ($d[$c]===null ? null : (float)$d[$c]);
                if (($before===null)!==($after===null) || $before!=$after) $differences[]=$c;
            }
            if (!$differences) continue;
            if ($lhp['diem_chinh_thuc'] && trim($reason)==='') throw new RuntimeException('Admin phải nhập lý do khi sửa điểm chính thức.');
            foreach ($differences as $c) $log->execute([$id,$user['id'],$c,$old[$id][$c],$d[$c],$reason ?: 'Lưu bản nháp',hien_thi_diem($old[$id],$c),hien_thi_diem($d,$c)]);
            $total=tinh_tong_ket($d,[]);
            $update->execute([$d['cc'],$d['tx'],$d['kt'],$d['thi'],$total,xet_ket_qua($total,$d),$user['id'],$id]); $changed++;
        }
        if ($finalize && !$lhp['diem_chinh_thuc']) {
            $pdo->prepare('UPDATE lop_hoc_phan SET diem_chinh_thuc=1,chot_luc=NOW(),chot_boi=? WHERE id=?')->execute([$user['id'],$lhpId]);
            foreach ($new as $id => $d) $log->execute([$id,$user['id'],'chot',null,null,'Lưu điểm chính thức',null,'Đã khóa']);
        }
        $pdo->prepare('UPDATE lop_hoc_phan SET phien_ban=phien_ban+1 WHERE id=?')->execute([$lhpId]);
        $pdo->commit();
        return ['changed'=>$changed,'finalized'=>$finalize || (bool)$lhp['diem_chinh_thuc']];
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
