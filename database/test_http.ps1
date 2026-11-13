param([string]$BaseUrl='http://localhost/BTL-GitHub/',[string]$Password='123456',[string]$Php='C:\xampp\php\php.exe')
$ErrorActionPreference='Stop'
$script:passed=0
$script:changed=@()            # accounts switched to a temp password; restored to default at the end
$fakeUser='khongtontai_kiemthu'
$tempPassword='KiemThu'+(Get-Random -Minimum 10000 -Maximum 99999)
function Pass([string]$Msg){$script:passed++;Write-Host "PASS $Msg"}
function Get-Csrf([string]$Html){[regex]::Match($Html,'name="csrf" value="([^"]+)"').Groups[1].Value}
function Post-Login($Session,[string]$Name,[string]$Pw){
    $page=Invoke-WebRequest ($BaseUrl+'login.php') -WebSession $Session -UseBasicParsing
    Invoke-WebRequest ($BaseUrl+'login.php') -WebSession $Session -Method POST -Body @{csrf=(Get-Csrf $page.Content);username=$Name;password=$Pw} -UseBasicParsing
}
function New-TestSession([string]$Name) {
    $session=New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $result=Post-Login $session $Name $Password
    $path=$result.BaseResponse.ResponseUri.AbsolutePath
    if($path -like '*login.php') {throw "Login failed: $Name"}
    if($path -like '*doi_mat_khau.php') {
        Pass "default password forces change ($Name)"
        $other=Invoke-WebRequest ($BaseUrl+'index.php') -WebSession $session -UseBasicParsing
        if($other.BaseResponse.ResponseUri.AbsolutePath -notlike '*doi_mat_khau.php'){throw "Forced change bypassed: $Name"}
        Pass "other pages blocked until password changed ($Name)"
        $weak=Invoke-WebRequest ($BaseUrl+'doi_mat_khau.php') -WebSession $session -Method POST -UseBasicParsing -Body @{csrf=(Get-Csrf $result.Content);mat_khau_cu=$Password;mat_khau_moi='abcdefgh';nhap_lai='abcdefgh'}
        if($weak.BaseResponse.ResponseUri.AbsolutePath -notlike '*doi_mat_khau.php' -or $weak.Content -notmatch 'alert-danger'){throw "Weak password accepted: $Name"}
        Pass "weak password rejected ($Name)"
        $script:changed+=$Name
        $ok=Invoke-WebRequest ($BaseUrl+'doi_mat_khau.php') -WebSession $session -Method POST -UseBasicParsing -Body @{csrf=(Get-Csrf $weak.Content);mat_khau_cu=$Password;mat_khau_moi=$tempPassword;nhap_lai=$tempPassword}
        if($ok.BaseResponse.ResponseUri.AbsolutePath -like '*doi_mat_khau.php'){throw "Password change failed: $Name"}
        Pass "password changed, access restored ($Name)"
    }
    return $session
}
function Check-Page($Session,[string]$Path) {
    $page=Invoke-WebRequest ($BaseUrl+$Path) -WebSession $Session -UseBasicParsing
    if($page.Content -match 'Fatal error|Warning:|Parse error') {throw "PHP error: $Path"}
    if($page.BaseResponse.ResponseUri.AbsolutePath -like '*login.php') {throw "Unexpectedly logged out: $Path"}
    Pass "GET $Path"
}
function Check-Denied($Session,[string]$Path,[int]$Code=403) {
    try {Invoke-WebRequest ($BaseUrl+$Path) -WebSession $Session -UseBasicParsing | Out-Null;throw "Access unexpectedly allowed: $Path"}
    catch {if($_.Exception.Response.StatusCode.value__ -ne $Code){throw};Pass "denied $Path ($Code)"}
}
try {
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
    catch {if($_.Exception.Response.StatusCode.value__ -ne 400){throw};Pass 'invalid CSRF rejected (400)'}

    # Logout only via POST + CSRF
    Invoke-WebRequest ($BaseUrl+'logout.php') -WebSession $gv -UseBasicParsing | Out-Null
    Check-Page $gv 'index.php'
    Pass 'GET logout.php does not log out'
    try {Invoke-WebRequest ($BaseUrl+'logout.php') -WebSession $gv -Method POST -Body @{csrf='invalid'} -UseBasicParsing | Out-Null;throw 'Logout CSRF unexpectedly accepted'}
    catch {if($_.Exception.Response.StatusCode.value__ -ne 400){throw};Pass 'logout with invalid CSRF rejected (400)'}
    $page=Invoke-WebRequest ($BaseUrl+'index.php') -WebSession $gv -UseBasicParsing
    $out=Invoke-WebRequest ($BaseUrl+'logout.php') -WebSession $gv -Method POST -Body @{csrf=(Get-Csrf $page.Content)} -UseBasicParsing
    if($out.BaseResponse.ResponseUri.Query -notlike '*msg=logout*'){throw 'POST logout failed'}
    Pass 'POST logout with CSRF logs out'

    # Brute-force: 5 failures lock the account, even with a fresh cookie
    for($i=1;$i -le 5;$i++){Post-Login (New-Object Microsoft.PowerShell.Commands.WebRequestSession) $fakeUser 'sai-mat-khau' | Out-Null}
    $locked=Post-Login (New-Object Microsoft.PowerShell.Commands.WebRequestSession) $fakeUser 'sai-mat-khau'
    if($locked.Content -notmatch 'th\S+ l\S+i sau'){throw 'Brute-force lockout not applied'}
    Pass 'login locked after 5 failures (new cookie each try)'
    Write-Output "SUCCESS: $script:passed HTTP checks. No grade changes submitted."
}
finally {
    # Restore default passwords and clear failed logins created by this test
    & $Php (Join-Path $PSScriptRoot 'dat_lai_mat_khau.php') @($script:changed) | Out-Null
    & $Php (Join-Path $PSScriptRoot 'dat_lai_mat_khau.php') '--chi-mo-khoa' $fakeUser | Out-Null
}
