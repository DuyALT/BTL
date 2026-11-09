param([string]$BaseUrl='http://localhost/BTL-GitHub/',[string]$Password='123456')
$ErrorActionPreference='Stop'
$script:passed=0
function New-TestSession([string]$Name) {
    $session=New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $page=Invoke-WebRequest ($BaseUrl+'login.php') -WebSession $session -UseBasicParsing
    $csrf=[regex]::Match($page.Content,'name="csrf" value="([^"]+)"').Groups[1].Value
    $result=Invoke-WebRequest ($BaseUrl+'login.php') -WebSession $session -Method POST -Body @{csrf=$csrf;username=$Name;password=$Password} -UseBasicParsing
    if($result.BaseResponse.ResponseUri.AbsolutePath -like '*login.php') {throw "Login failed: $Name"}
    return $session
}
function Check-Page($Session,[string]$Path) {
    $page=Invoke-WebRequest ($BaseUrl+$Path) -WebSession $Session -UseBasicParsing
    if($page.Content -match 'Fatal error|Warning:|Parse error') {throw "PHP error: $Path"}
    $script:passed++;Write-Output "PASS GET $Path"
}
function Check-Denied($Session,[string]$Path,[int]$Code=403) {
    try {Invoke-WebRequest ($BaseUrl+$Path) -WebSession $Session -UseBasicParsing | Out-Null;throw "Access unexpectedly allowed: $Path"}
    catch {if($_.Exception.Response.StatusCode.value__ -ne $Code){throw};$script:passed++;Write-Output "PASS denied $Path ($Code)"}
}
$admin=New-TestSession 'admin'
foreach($path in @('index.php','admin/lop.php','admin/namhoc.php','admin/hocky.php','admin/sinhvien.php?edit=1','admin/giangvien.php','admin/lophocphan.php','admin/monhoc.php','admin/users.php','admin/saoluu.php','admin/lhp_sinhvien.php?id=1','tra_cuu.php','giangvien/nhap_diem.php?lhp=1','giangvien/lich_su.php','giangvien/in_bang_diem.php?lhp=1','sinhvien/bang_diem.php?sv=1','thongke.php','tra_cuu.php?export=csv','thongke.php?export=csv')) {Check-Page $admin $path}
$sv=New-TestSession '2251000001'
foreach($path in @('index.php','sinhvien/tong_quan.php','sinhvien/bang_diem.php','tra_cuu.php','tra_cuu.php?export=csv')) {Check-Page $sv $path}
Check-Denied $sv 'admin/saoluu.php'
Check-Denied $sv 'giangvien/nhap_diem.php?lhp=1'
Check-Denied $sv 'database/quanlydiem.sql'
$gv=New-TestSession 'gv001'
foreach($path in @('index.php','tra_cuu.php','thongke.php','giangvien/nhap_diem.php?lhp=1')) {Check-Page $gv $path}
Check-Denied $gv 'giangvien/nhap_diem.php?lhp=3'
Check-Denied $gv 'admin/users.php'
try {Invoke-WebRequest ($BaseUrl+'giangvien/nhap_diem.php?lhp=1') -WebSession $admin -Method POST -Body @{csrf='invalid';action='finalize'} -UseBasicParsing | Out-Null;throw 'CSRF unexpectedly accepted'}
catch {if($_.Exception.Response.StatusCode.value__ -ne 400){throw};$script:passed++;Write-Output 'PASS invalid CSRF rejected (400)'}
Write-Output "SUCCESS: $script:passed HTTP checks. No grade changes submitted."
