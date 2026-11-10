<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Robo\Core\AuditLogger;
use Robo\Core\Csrf;
use Robo\Core\Session;
use Robo\Domain\CompetitionRepository;
use Robo\Domain\RankingService;
use Robo\Security\AuthService;
use Robo\Security\RateLimiter;

const ROBO_ROOT = __DIR__;
const ROBO_BASE = '/robo';

function redirect_to(string $route): never { header('Location: ' . ROBO_BASE . $route, true, 303); exit; }
function render_view(string $view, array $data = []): never { extract($data, EXTR_SKIP); require ROBO_ROOT . '/views/' . $view . '.php'; exit; }
function client_ip(): string { return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
function json_response(array $payload, int $status = 200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); exit; }
function request_path(): string { $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/'); return trim((string) preg_replace('#^/robo/?#', '', $path), '/'); }
function input_json(): array { $body = file_get_contents('php://input'); $data = json_decode($body === false ? '' : $body, true); return is_array($data) ? $data : []; }
function csrf_request(): void { Csrf::verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? null); }

function current_user(PDO $pdo): ?array {
    $id = Session::userId();
    if ($id === null) return null;
    $query = $pdo->prepare('SELECT u.id, u.username, u.role_code, r.permissions_json FROM users u JOIN roles r ON r.code = u.role_code WHERE u.id = :id AND u.is_active = 1');
    $query->execute(['id' => $id]);
    $user = $query->fetch();
    if ($user === false) { Session::logout(); return null; }
    $user['permissions'] = json_decode((string) $user['permissions_json'], true, 512, JSON_THROW_ON_ERROR);
    return $user;
}
function require_user(PDO $pdo): array { $user = current_user($pdo); if ($user === null) redirect_to('/login'); return $user; }
function permission(array $user, string $name): bool { return $user['role_code'] === 'administrator' || in_array('*', $user['permissions'], true) || in_array($name, $user['permissions'], true); }
function require_permission(array $user, string $name): void { if (!permission($user, $name)) json_response(['error' => 'Pro tuto akci nemáte oprávnění.'], 403); }
function active_competition(PDO $pdo): array { return (new CompetitionRepository($pdo))->active(); }
function state(PDO $pdo, int $competitionId): array {
    $sql = 'SELECT t.id, t.team_number AS number, t.school_name AS school, t.contestant_names AS members, i.criterion_1, i.criterion_2, i.criterion_3, i.note, COALESCE(i.penalty_seconds, 0) AS technical_penalty, COALESCE(i.is_passed, 0) AS passed, COALESCE(r.checkpoint_1,0) checkpoint_1, COALESCE(r.checkpoint_2,0) checkpoint_2, COALESCE(r.checkpoint_3,0) checkpoint_3, COALESCE(r.checkpoint_4,0) checkpoint_4, COALESCE(r.checkpoint_5,0) checkpoint_5, COALESCE(r.checkpoint_6,0) checkpoint_6, COALESCE(r.finish_count,0) finish, r.lap_1_ms time_1, r.lap_2_ms time_2, r.lap_3_ms time_3, r.lap_4_ms time_4, r.lap_5_ms time_5, r.best_time_ms best_time, COALESCE(r.did_not_stop,0) did_not_stop, COALESCE(r.is_confirmed,0) confirmed FROM teams t LEFT JOIN technical_inspections i ON i.team_id=t.id AND i.competition_id=t.competition_id LEFT JOIN rides r ON r.team_id=t.id AND r.competition_id=t.competition_id WHERE t.competition_id=:competition_id ORDER BY t.team_number';
    $query = $pdo->prepare($sql); $query->execute(['competition_id' => $competitionId]); $teams = $query->fetchAll();
    foreach ($teams as &$team) { for ($i=1;$i<=3;$i++) { $team['condition_'.$i] = match ($team['criterion_'.$i] ?? 'unknown') { 'passed' => 'pass', 'failed' => 'fail', default => 'pending' }; } $team['complete'] = !in_array('unknown', [$team['criterion_1'] ?? 'unknown', $team['criterion_2'] ?? 'unknown', $team['criterion_3'] ?? 'unknown'], true); }
    unset($team); return $teams;
}
function ranking(PDO $pdo, int $competitionId): array {
    $items = (new RankingService($pdo))->qualification($competitionId); $rows=[];
    foreach ($items as $row) { $rows[]=['team_id'=>(int)$row['team_id'],'number'=>(int)$row['team_number'],'school'=>(string)$row['school_name'],'members'=>(string)$row['contestant_names'],'checkpoint_1'=>(int)($row['checkpoint_1']??0),'checkpoint_2'=>(int)($row['checkpoint_2']??0),'checkpoint_3'=>(int)($row['checkpoint_3']??0),'checkpoint_4'=>(int)($row['checkpoint_4']??0),'checkpoint_5'=>(int)($row['checkpoint_5']??0),'checkpoint_6'=>(int)($row['checkpoint_6']??0),'finish'=>(int)($row['finish_count']??0),'best_time'=>$row['best_time_ms']===null?null:(int)$row['best_time_ms'],'did_not_stop'=>(int)($row['did_not_stop']??0),'stop_penalty'=>(int)($row['stop_penalty_seconds']??0),'technical_penalty'=>(int)($row['technical_penalty_seconds']??0),'total_time'=>$row['final_time_ms']===null?null:(int)$row['final_time_ms'],'rank'=>(int)$row['rank'],'furthest_checkpoint'=>'checkpoint_'.(int)$row['furthest_checkpoint']]; }
    return $rows;
}
function page_state(array $competition, array $user, string $page, int $pending): array { return ['year'=>(string)$competition['competition_year'],'locale'=>(string)$competition['locale'],'user'=>(string)$user['username'],'teams'=>(int)$competition['team_count'],'pending'=>$pending,'saved_at'=>date('H:i:s'),'page'=>$page]; }
function xlsx_column(int $number): string { $label=''; while($number>0){$number--; $label=chr(65+$number%26).$label; $number=intdiv($number,26);} return $label; }
function xlsx_download(string $filename, array $sheets): never {
    if (!class_exists('ZipArchive')) { http_response_code(501); exit('Server nemá dostupné rozšíření ZIP potřebné pro export XLSX.'); }
    $temp=tempnam(sys_get_temp_dir(),'robo-xlsx-'); $zip=new ZipArchive(); $zip->open($temp,ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.implode('',array_map(static fn($i)=>'<Override PartName="/xl/worksheets/sheet'.($i+1).'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>',array_keys($sheets))).'</Types>');
    $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $book=[];$rels=[];foreach(array_values($sheets) as $offset=>$sheet){$index=$offset+1;$book[]='<sheet name="'.htmlspecialchars($sheet['name'],ENT_XML1|ENT_QUOTES,'UTF-8').'" sheetId="'.$index.'" r:id="rId'.$index.'"/>';$rels[]='<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>'; $xml='<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';foreach($sheet['rows'] as $rowIndex=>$row){$xml.='<row r="'.($rowIndex+1).'">';foreach(array_values($row) as $column=>$value){$cell=xlsx_column($column+1).($rowIndex+1);$xml.='<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars((string)$value,ENT_XML1|ENT_QUOTES,'UTF-8').'</t></is></c>';} $xml.='</row>';} $xml.='</sheetData></worksheet>';$zip->addFromString('xl/worksheets/sheet'.$index.'.xml',$xml);}
    $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.implode('',$book).'</sheets></workbook>');$zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.implode('',$rels).'</Relationships>');$zip->close();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.filesize($temp));readfile($temp);@unlink($temp);exit;
}
function display_time(?int $milliseconds): string { if($milliseconds===null||$milliseconds<=0)return '';return sprintf('%02d:%02d:%03d',intdiv($milliseconds,60000),intdiv($milliseconds%60000,1000),$milliseconds%1000); }
function write_php_config(string $dsn, string $dbUser, string $dbPassword, bool $secure): void { $target=ROBO_ROOT.'/config/local.php'; $temp=$target.'.tmp'; $data=['db_dsn'=>$dsn,'db_user'=>$dbUser,'db_password'=>$dbPassword,'app_secret'=>bin2hex(random_bytes(32)),'secure_cookie'=>$secure,'base_path'=>ROBO_BASE]; if (file_put_contents($temp,"<?php\ndeclare(strict_types=1);\n\nreturn ".var_export($data,true).";\n",LOCK_EX)===false || !rename($temp,$target)) { @unlink($temp); throw new RuntimeException('Konfiguraci nelze uložit.'); } @chmod($target,0640); }
function seed_database(PDO $pdo, string $admin, string $password): void { $pdo->beginTransaction(); try { $pdo->prepare('INSERT INTO competitions (competition_year, competition_date, locale, team_count, is_active) VALUES (:year,:date,\'cs\',16,1)')->execute(['year'=>(int)date('Y'),'date'=>date('Y-m-d')]); $cid=(int)$pdo->lastInsertId(); $pdo->prepare('INSERT INTO users (username,password_hash,role_code) VALUES (:username,:password_hash,\'administrator\')')->execute(['username'=>$admin,'password_hash'=>password_hash($password,PASSWORD_DEFAULT)]); $team=$pdo->prepare('INSERT INTO teams (competition_id,team_number) VALUES (:competition_id,:team_number)'); for($i=1;$i<=16;$i++) $team->execute(['competition_id'=>$cid,'team_number'=>$i]); $penalty=$pdo->prepare('INSERT INTO penalties (competition_id,code,label_cs,label_en,seconds_value) VALUES (:competition_id,:code,:cs,:en,:value)'); $penalty->execute(['competition_id'=>$cid,'code'=>'technical','cs'=>'Technická přejímka','en'=>'Technical inspection','value'=>90]); $penalty->execute(['competition_id'=>$cid,'code'=>'not_stopped','cs'=>'Nezastavení','en'=>'Did not stop','value'=>5]); $pdo->commit(); } catch(Throwable $e) { $pdo->rollBack(); throw $e; } }

if (!is_file(ROBO_ROOT.'/config/local.php')) { header('Location: '.ROBO_BASE.'/install.php',true,302); exit; }
$config=robo_config(ROBO_ROOT); Session::start($config->bool('secure_cookie', true)); $pdo=robo_database($config)->pdo(); $route=request_path(); $method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));
if ($route==='login') { $competition=active_competition($pdo); if($method==='POST'){ csrf_request(); $auth=new AuthService($pdo,new RateLimiter($pdo),new AuditLogger($pdo)); $result=$auth->attempt(trim((string)($_POST['username']??'')),(string)($_POST['password']??''),client_ip(),new DateTimeImmutable()); if($result['ok']) redirect_to('/technical'); render_view('login',['csrf'=>Csrf::token(),'year'=>$competition['competition_year'],'locale'=>strtoupper((string)$competition['locale']),'error'=>'Přihlášení se nezdařilo. Zkontrolujte zadané údaje nebo kontaktujte správce.']); } render_view('login',['csrf'=>Csrf::token(),'year'=>$competition['competition_year'],'locale'=>strtoupper((string)$competition['locale']),'error'=>null]); }
if ($route==='logout') { csrf_request(); Session::logout(); redirect_to('/login'); }
if ($route==='public' || $route==='api/standings') { $competition=active_competition($pdo); $publicRows=ranking($pdo,(int)$competition['id']); if($route==='api/standings') json_response(['rows'=>$publicRows,'updated_at'=>gmdate(DATE_ATOM)]); render_view('public-display',['locale'=>(string)$competition['locale'],'year'=>(string)$competition['competition_year'],'rows'=>$publicRows]); }
$user=require_user($pdo); $competition=active_competition($pdo); $cid=(int)$competition['id'];
if ($route==='api/technical' && $method==='POST') {
    csrf_request(); require_permission($user,'technical.edit'); $input=input_json(); $teamId=(int)($input['team_id']??0); $field=(string)($input['field']??'');
    if ($teamId<1 || !in_array($field,['condition_1','condition_2','condition_3','note'],true)) json_response(['error'=>'Neplatný požadavek.'],422);
    $team=$pdo->prepare('SELECT id FROM teams WHERE id=:id AND competition_id=:competition_id'); $team->execute(['id'=>$teamId,'competition_id'=>$cid]); if($team->fetch()===false) json_response(['error'=>'Tým neexistuje.'],404);
    $existing=$pdo->prepare('SELECT criterion_1,criterion_2,criterion_3,note FROM technical_inspections WHERE competition_id=:competition_id AND team_id=:team_id'); $existing->execute(['competition_id'=>$cid,'team_id'=>$teamId]); $previous=$existing->fetch() ?: ['criterion_1'=>'unknown','criterion_2'=>'unknown','criterion_3'=>'unknown','note'=>''];
    if ($field==='note') $previous['note']=substr((string)($input['value']??''),0,1000); else { $states=['pending'=>'unknown','pass'=>'passed','fail'=>'failed']; $previous['criterion_'.substr($field,-1)]=$states[(string)($input['value']??'')]??null; if($previous['criterion_'.substr($field,-1)]===null) json_response(['error'=>'Neplatný stav.'],422); }
    $failed=in_array('failed',[$previous['criterion_1'],$previous['criterion_2'],$previous['criterion_3']],true); $complete=!in_array('unknown',[$previous['criterion_1'],$previous['criterion_2'],$previous['criterion_3']],true);
    $penalty=0; if($failed){$p=$pdo->prepare("SELECT seconds_value FROM penalties WHERE competition_id=:competition_id AND code='technical'");$p->execute(['competition_id'=>$cid]);$penalty=(int)$p->fetchColumn();}
    $save=$pdo->prepare('INSERT INTO technical_inspections (competition_id,team_id,criterion_1,criterion_2,criterion_3,note,penalty_seconds,is_passed,completed_at,updated_by) VALUES (:competition_id,:team_id,:criterion_1,:criterion_2,:criterion_3,:note,:penalty_seconds,:is_passed,:completed_at,:updated_by) ON DUPLICATE KEY UPDATE criterion_1=VALUES(criterion_1),criterion_2=VALUES(criterion_2),criterion_3=VALUES(criterion_3),note=VALUES(note),penalty_seconds=VALUES(penalty_seconds),is_passed=VALUES(is_passed),completed_at=VALUES(completed_at),updated_by=VALUES(updated_by)');
    $save->execute(['competition_id'=>$cid,'team_id'=>$teamId,'criterion_1'=>$previous['criterion_1'],'criterion_2'=>$previous['criterion_2'],'criterion_3'=>$previous['criterion_3'],'note'=>$previous['note'],'penalty_seconds'=>$penalty,'is_passed'=>$complete&&!$failed?1:0,'completed_at'=>$complete?date('Y-m-d H:i:s'):null,'updated_by'=>$user['id']]); json_response(['ok'=>true,'penalty'=>$penalty,'passed'=>$complete&&!$failed]);
}
if (preg_match('#^api/tracks/([12])$#',$route,$apiTrack)===1 && $method==='POST') {
    csrf_request(); $track=(int)$apiTrack[1]; require_permission($user,'track_'.$track.'.edit'); $input=input_json(); $teamId=(int)($input['team_id']??0); $field=(string)($input['field']??''); $allowed=['checkpoint_1','checkpoint_2','checkpoint_3','checkpoint_4','checkpoint_5','checkpoint_6','finish_count','lap_1_ms','lap_2_ms','lap_3_ms','lap_4_ms','lap_5_ms','did_not_stop','is_confirmed']; if($teamId<1||!in_array($field,$allowed,true)) json_response(['error'=>'Neplatný požadavek.'],422);
    $team=$pdo->prepare('SELECT team_number FROM teams WHERE id=:id AND competition_id=:competition_id');$team->execute(['id'=>$teamId,'competition_id'=>$cid]);$teamRow=$team->fetch(); if($teamRow===false||(int)$teamRow['team_number']%2!==$track%2) json_response(['error'=>'Tým nepatří na tuto dráhu.'],422);
    $value=$input['value']??null; if(str_starts_with($field,'lap_')) $value=$value===null||$value===''?null:(int)$value; elseif(in_array($field,['did_not_stop','is_confirmed'],true)) $value=!empty($value)?1:0; else $value=max(0,min(999999,(int)$value));
    $insert=$pdo->prepare('INSERT INTO rides (competition_id,team_id,track_number,updated_by) VALUES (:competition_id,:team_id,:track_number,:updated_by) ON DUPLICATE KEY UPDATE updated_by=VALUES(updated_by)');$insert->execute(['competition_id'=>$cid,'team_id'=>$teamId,'track_number'=>$track,'updated_by'=>$user['id']]);
    $update=$pdo->prepare('UPDATE rides SET '.$field.'=:value, updated_by=:user_id WHERE competition_id=:competition_id AND team_id=:team_id');$update->execute(['value'=>$value,'user_id'=>$user['id'],'competition_id'=>$cid,'team_id'=>$teamId]);
    $times=$pdo->prepare('SELECT lap_1_ms,lap_2_ms,lap_3_ms,lap_4_ms,lap_5_ms,did_not_stop,is_confirmed FROM rides WHERE competition_id=:competition_id AND team_id=:team_id');$times->execute(['competition_id'=>$cid,'team_id'=>$teamId]);$ride=$times->fetch();$laps=array_filter([$ride['lap_1_ms'],$ride['lap_2_ms'],$ride['lap_3_ms'],$ride['lap_4_ms'],$ride['lap_5_ms']],static fn($v)=>$v!==null&&(int)$v>0);$best=$laps===[]?null:min(array_map('intval',$laps));$p=$pdo->prepare("SELECT seconds_value FROM penalties WHERE competition_id=:competition_id AND code='not_stopped'");$p->execute(['competition_id'=>$cid]);$penalty=(int)$p->fetchColumn();$final=$pdo->prepare('UPDATE rides SET best_time_ms=:best, stop_penalty_seconds=:penalty, confirmed_at=:confirmed_at WHERE competition_id=:competition_id AND team_id=:team_id');$final->execute(['best'=>$best,'penalty'=>(int)$ride['did_not_stop']===1?$penalty:0,'confirmed_at'=>(int)$ride['is_confirmed']===1?date('Y-m-d H:i:s'):null,'competition_id'=>$cid,'team_id'=>$teamId]); json_response(['ok'=>true,'best_time'=>$best]);
}
if ($route==='technical') { require_permission($user,'technical.view'); $teams=state($pdo,$cid); render_view('technical',['title'=>'Technická kontrola vozítek','view'=>page_state($competition,$user,'technical',count(array_filter($teams,static fn($t)=>!$t['complete']))),'csrf'=>Csrf::token(),'teams'=>$teams,'labels'=>[]]); }
if (preg_match('#^track-([12])$#',$route,$match)===1) { $track=(int)$match[1]; require_permission($user,'track_'.$track.'.view'); $teams=array_values(array_filter(state($pdo,$cid),static fn($t)=>$t['number']%2===$track%2)); render_view('race',['title'=>'Nejlepší jízda — Dráha '.$track,'view'=>page_state($competition,$user,'track-'.$track,count(array_filter($teams,static fn($t)=>!$t['confirmed']))),'csrf'=>Csrf::token(),'teams'=>$teams,'track'=>$track]); }
$rows=ranking($pdo,$cid);
if ($route==='export/playoff.xlsx') {
    require_permission($user,'results.view');
    $export=[['ID týmu','Škola / adresa školy','Jméno soutěžícího','Nejlepší čas jízdy']];
    foreach($rows as $row) $export[]=[(string)$row['number'],$row['school'],$row['members'],display_time($row['best_time'])];
    xlsx_download('ROBO-Playoff.xlsx',[['name'=>'Pojmenování týmů','rows'=>$export]]);
}
if ($route==='export/standings.xlsx') {
    require_permission($user,'results.view');
    $qualification=[['Pořadí','Tým','Bod 1','Bod 2','Bod 3','Bod 4','Bod 5','Bod 6','Cíl','Nejlepší čas (ms)','Penalizace zastavení (s)','Technická penalizace (s)','Celkový čas (ms)']];$participants=[['Tým','Škola','Soutěžící','Pořadí']];
    foreach($rows as $row){$qualification[]=[(string)$row['rank'],(string)$row['number'],(string)$row['checkpoint_1'],(string)$row['checkpoint_2'],(string)$row['checkpoint_3'],(string)$row['checkpoint_4'],(string)$row['checkpoint_5'],(string)$row['checkpoint_6'],(string)$row['finish'],(string)($row['best_time']??''),(string)$row['stop_penalty'],(string)$row['technical_penalty'],(string)($row['total_time']??'')];$participants[]=[(string)$row['number'],$row['school'],$row['members'],(string)$row['rank']];}
    xlsx_download('ROBO-vysledky.xlsx',[['name'=>'Kvalifikace','rows'=>$qualification],['name'=>'Týmy','rows'=>$participants]]);
}
if ($route==='print/standings') render_view('public-display',['locale'=>(string)$competition['locale'],'year'=>(string)$competition['competition_year'],'rows'=>$rows]);
if ($route===''||$route==='standings') { require_permission($user,'results.view'); render_view('standings',['title'=>'Celkové pořadí','view'=>page_state($competition,$user,'standings',0),'csrf'=>Csrf::token(),'rows'=>$rows]); }
if ($route==='public') render_view('public-display',['locale'=>(string)$competition['locale'],'year'=>(string)$competition['competition_year'],'rows'=>$rows]);
if ($route==='system') { require_permission($user,'system.view'); $links=[['href'=>'#competition','label'=>'Nastavení — Rok aktuální soutěže a jazyk'],['href'=>'#texts','label'=>'Nastavení — Texty stránek (CZ/EN)'],['href'=>'#penalties','label'=>'Nastavení — Penalizace soutěže'],['href'=>'#teams','label'=>'Nastavení — Jména soutěžících a škol'],['href'=>'#users','label'=>'Nastavení — Uživatelé a role'],['href'=>'#logs','label'=>'Nastavení — Log']]; render_view('admin',['title'=>'Správa a nastavení systému','view'=>page_state($competition,$user,'system',0),'csrf'=>Csrf::token(),'settingsLinks'=>$links,'blocks'=>[],'backups'=>[],'releaseMessage'=>'Verzi lze ověřit po zveřejnění prvního vydání.','phpVersion'=>PHP_VERSION,'serverName'=>php_uname('s')]); }
http_response_code(404); echo 'Stránka nebyla nalezena.';
