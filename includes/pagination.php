<?php
/** Phân trang dùng chung cho các danh sách đã lọc; giữ bộ lọc trên URL. */
function phan_trang(array $rows, int $size=20): array {
    $total=count($rows);$pages=max(1,(int)ceil($total/$size));$page=max(1,min($pages,(int)($_GET['page']??1)));
    return [array_slice($rows,($page-1)*$size,$size),['total'=>$total,'pages'=>$pages,'page'=>$page]];
}
function hien_phan_trang(array $p): void {
    echo '<nav class="d-flex align-items-center flex-wrap gap-2 my-3 no-print" aria-label="Phân trang"><span class="text-muted small">Tổng '.(int)$p['total'].' bản ghi</span>';
    for($i=1;$i<=$p['pages'];$i++) {
        if($i!==1 && $i!==$p['pages'] && abs($i-$p['page'])>2) continue;
        $q=$_GET;$q['page']=$i;
        echo '<a class="btn btn-sm '.($i===$p['page']?'btn-primary':'btn-outline-secondary').'" href="?'.h(http_build_query($q)).'">'.$i.'</a>';
    }
    echo '</nav>';
}
