<?php
class AUTH {
    var $DB;
    var $user = array(
     'id'    => -1,
     'username'  => 'Guest',
     'g_id' => 1
    );

private function initializeAuth()
{
    $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
    $activeRealmId = function_exists('spp_current_realm_id')
        ? spp_current_realm_id(is_array($realmDbMap) ? $realmDbMap : array())
        : 1;
    $this->DB = spp_get_pdo('realmd', $activeRealmId);

    $this->check();
    $this->user['ip'] = $_SERVER['REMOTE_ADDR'];
    if ((int)spp_config_generic('onlinelist_on', 0)) {
        if ($this->user['id'] < 1) {
            $this->onlinelist_addguest();
        } else {
            $this->onlinelist_add();
        }
        $this->onlinelist_update();
    }
}

public function __construct($DB = null, $confs = null)
{
    $this->initializeAuth();
}

function check()
{
    $siteCookieName = (string)spp_config_generic('site_cookie', 'sppArmory');
    if(isset($_COOKIE[$siteCookieName])){
        $cookie = function_exists('spp_parse_account_cookie_payload')
            ? spp_parse_account_cookie_payload($_COOKIE[$siteCookieName])
            : null;
        if (empty($cookie['user_id']) || empty($cookie['account_key'])) {
            $this->logout();
            $this->setgroup();
            return false;
        }

        $stmt = $this->DB->prepare("
            SELECT * FROM account
            LEFT JOIN website_accounts ON account.id=website_accounts.account_id
            LEFT JOIN website_account_groups ON website_accounts.g_id=website_account_groups.g_id
            WHERE id = ?");
        $stmt->execute([(int)$cookie['user_id']]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$res || get_banned($res['id'], 1)== TRUE){
            $this->setgroup();
            $this->logout();
            return false;
        }



	if (matchAccountKey($cookie['user_id'], $cookie['account_key'])){
    unset($res['sha_pass_hash']);

    // Auto-grant website admin flags based on game GM level so any GM account
    // can access the admin panel without requiring a manual DB update.
    $gmLevel = (int)($res['gmlevel'] ?? 0);
    if ($gmLevel >= 3) {
        $res['g_is_admin'] = 1;
    }
    if ($gmLevel >= 4) {
        $res['g_is_supadmin'] = 1;
    }

    $this->user = $res;

    // Always load characters once the user is authenticated
    $this->load_characters_for_user();

    // Ensure global array exists early for header/menu use
    if (!empty($GLOBALS['account_characters']) && is_array($GLOBALS['account_characters'])) {
        $GLOBALS['has_characters'] = true;
    }

    return true;
}
 else {
            $this->setgroup();
            return false;
        }
    } else {
        $this->setgroup();
        return false;
    }
}

// === Load Characters for Logged-in User (All Realms) ===



function load_characters_for_user() {
    if (empty($this->user['id'])) {
        $GLOBALS['account_characters'] = [];
        $GLOBALS['characters'] = [];
        $GLOBALS['has_characters'] = false;
        return;
    }

    $realmDbMap = $GLOBALS['realmDbMap'] ?? [];
    $db         = $GLOBALS['db'] ?? [];
    $runtimeRealmMap = function_exists('spp_realm_runtime_enabled_realm_map')
        ? spp_realm_runtime_enabled_realm_map(is_array($realmDbMap) ? $realmDbMap : array())
        : $realmDbMap;
    $runtimeState = function_exists('spp_realm_runtime_resolve_active_realm_id')
        ? spp_realm_runtime_resolve_active_realm_id(is_array($runtimeRealmMap) ? $runtimeRealmMap : array())
        : array();
    $multirealmEnabled = (int)($runtimeState['multirealm'] ?? (function_exists('spp_bootstrap_multirealm_enabled') ? spp_bootstrap_multirealm_enabled() : 0)) === 1;

    if (!is_array($realmDbMap) || empty($runtimeRealmMap) || empty($db['host'])) {
        $GLOBALS['account_characters'] = [];
        $GLOBALS['characters'] = [];
        $GLOBALS['has_characters'] = false;
        return;
    }

    $GLOBALS['account_characters'] = [];
    $GLOBALS['characters'] = [];
    $GLOBALS['has_characters'] = false;

    // Build PDO options once
    $pdoOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $dsnBase = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
    $availableDatabases = null;

    try {
        $serverPdo = new PDO($dsnBase, $db['user'], $db['pass'], $pdoOptions);
        $availableDatabases = $serverPdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!is_array($availableDatabases)) {
            $availableDatabases = [];
        }
        $availableDatabases = array_fill_keys(array_map('strval', $availableDatabases), true);
    } catch (Exception $e) {
        $availableDatabases = null;
    }

    if (!$multirealmEnabled) {
        $activeRealmId = (int)($runtimeState['active_realm_id'] ?? 0);
        if ($activeRealmId <= 0 || !isset($runtimeRealmMap[$activeRealmId])) {
            $activeRealmId = function_exists('spp_current_realm_id')
                ? spp_current_realm_id(is_array($runtimeRealmMap) ? $runtimeRealmMap : array())
                : (int)array_key_first($runtimeRealmMap);
        }
        $runtimeRealmMap = isset($runtimeRealmMap[$activeRealmId])
            ? array($activeRealmId => $runtimeRealmMap[$activeRealmId])
            : array();
    }

    foreach ($runtimeRealmMap as $id => $realmInfo) {
        // Use DB names directly from the map — do NOT go through spp_get_pdo() /
        // spp_resolve_realm_id(), which would override $id with GET/cookie state
        // and cause the same realm's characters to be loaded multiple times.
        $realmdDbName = (string)($realmInfo['realmd'] ?? '');
        $charsDbName  = (string)($realmInfo['chars']  ?? '');
        if ($realmdDbName === '' || $charsDbName === '') {
            continue;
        }
        if (is_array($availableDatabases) && !isset($availableDatabases[$charsDbName])) {
            continue;
        }

        try {
            $charPdo = new PDO("{$dsnBase};dbname={$charsDbName}", $db['user'], $db['pass'], $pdoOptions);
            $stmt = $charPdo->prepare("SELECT guid, name, race, class, level FROM characters WHERE account=? ORDER BY level DESC");
            $stmt->execute([(int)$this->user['id']]);
            $chars = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!is_array($chars) || empty($chars)) {
                continue;
            }

            $realmName = '';
            if (function_exists('spp_get_armory_realm_name')) {
                $realmName = (string)(spp_get_armory_realm_name((int)$id) ?? '');
            }
            if ($realmName === '') {
                $realmName = 'Realm ' . (int)$id;
            }

            foreach ($chars as &$char) {
                $char['realm_id']   = $id;
                $char['realm_name'] = $realmName;
                $char['account']    = (int)$this->user['id'];
            }
            unset($char);

            $GLOBALS['account_characters'] = array_merge($GLOBALS['account_characters'], $chars);

        } catch (Exception $e) {
            $message = (string)$e->getMessage();
            if (stripos($message, 'Unknown database') !== false) {
                continue;
            }
            error_log("[AUTH] Failed loading characters for realm {$id}: " . $message);
        }
    }

    // Backward-compatible alias for older consumers. Page templates may define a
    // top-level $characters variable later, so account-aware code should prefer
    // account_characters over this legacy global.
    $GLOBALS['characters'] = $GLOBALS['account_characters'];
    $GLOBALS['has_characters'] = !empty($GLOBALS['account_characters']);
}


    function setgroup($gid=1) // 1 - guest, 5- banned
    {
        $guest_g = array($this->getgroup($gid));
        $this->user = array_merge($this->user,$guest_g);
    }

    function login($params)
    {
        $success = 1;
        $username = strtoupper(trim((string)($params['username'] ?? '')));

        if (empty($params)) {
            spp_login_feedback_set('Please enter your username and password.');
            return false;
        }
        if (empty($params['username'])){
            spp_login_feedback_set('Please enter your username.');
            $success = 0;
        }
        if (empty($params['password'])){
            spp_login_feedback_set('Please enter your password.');
            $success = 0;
        }
        $lockState = function_exists('spp_login_throttle_is_locked')
            ? spp_login_throttle_is_locked($username)
            : array('locked' => false, 'retry_after' => 0);
        if (!empty($lockState['locked'])) {
            $retryAfter = max(1, (int)($lockState['retry_after'] ?? 0));
            spp_login_feedback_set('Too many login attempts. Please wait ' . $retryAfter . ' seconds and try again.');
            return false;
        }
        $stmt = $this->DB->prepare("SELECT `id`,`username`,`s`,`v`,`locked` FROM `account` WHERE `username` = ?");
        $stmt->execute([$username]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res) {
            spp_login_throttle_record_failure($username);
            spp_login_feedback_set('Invalid username or password.');
            return false;
        }

        if($res['id'] < 1){$success = 0;spp_login_feedback_set('Invalid username or password.');}
        if(get_banned($res['id'], 1)== TRUE){
            spp_login_feedback_set('Your account is currently banned.');
            $success = 0;
        }
        if($success!=1) return false;
        if (verifySRP6($username, $params['password'], $res['s'], $res['v'])) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $this->user['id'] = $res['id'];
    $this->user['username'] = $res['username'];
    $this->user['name'] = $res['username'];

    
    // Load all realm characters for this account
    $this->load_characters_for_user();

    $generated_key = $this->generate_key();
    addOrUpdateAccountKeys($res['id'], $generated_key);

    $uservars_hash = serialize([$res['id'], $generated_key]);
    $cookie_expire_time = (int)spp_config_generic('account_key_retain_length', 0);
    if (!$cookie_expire_time) {
        $cookie_expire_time = 60 * 60 * 24 * 365; // default 1 year
    }

    $cookie_name  = (string)spp_config_generic('site_cookie', 'sppArmory');
    $cookie_delay = time() + $cookie_expire_time;

    if (function_exists('spp_set_site_cookie')) {
        spp_set_site_cookie($cookie_name, $uservars_hash, $cookie_delay);
    } else {
        setcookie($cookie_name, $uservars_hash, $cookie_delay, '/');
    }
    spp_login_throttle_clear($username);

    if ((int)spp_config_generic('onlinelist_on', 0)) {
        $this->onlinelist_delguest();
    }

    return true;

} else {
    spp_login_throttle_record_failure($username);
    spp_login_feedback_set('Invalid username or password.');
    return false;
}}

    function logout()
    {
        $cookieName = (string)spp_config_generic('site_cookie', 'sppArmory');
        if (function_exists('spp_set_site_cookie')) {
            spp_set_site_cookie($cookieName, '', time() - 3600);
        } else {
            setcookie($cookieName, '', time()-3600, '/');
        }
        if (!empty($this->user['id'])) {
            removeAccountKeyForUser($this->user['id']);
        }
        if((int)spp_config_generic('onlinelist_on', 0))$this->onlinelist_del(); // !!
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['spp_login_feedback'], $_SESSION['spp_login_throttle'], $_SESSION['spp_csrf_tokens']);
            session_regenerate_id(true);
        }
        $this->setgroup();
    }

    function check_pm()
    {
        $stmt = $this->DB->prepare("SELECT count(*) FROM website_pms WHERE owner_id=? AND showed=0");
        $stmt->execute([(int)$this->user['id']]);
        return $stmt->fetchColumn();
    }
    /*
    function lastvisit_update($uservars)
    {
        if($uservars['id']>0){
            if(time() - $uservars['last_visit'] > 60*10){
                $this->DB->query("UPDATE members SET last_visit=?d WHERE id=?d LIMIT 1",time(),$uservars['id']);
            }
        }
    }
    */
    function register($params, $account_extend = false)
    {
        $success = 1;
        $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
        $activeRealmId = function_exists('spp_current_realm_id')
            ? spp_current_realm_id(is_array($realmDbMap) ? $realmDbMap : array())
            : 1;
        if(empty($params)) return false;
        if(empty($params['username'])){
            output_message('alert','You did not provide your username');
            $success = 0;
        }
        //if(empty($params['sha_pass_hash']) || $params['sha_pass_hash']!=$params['sha_pass_hash2']){
        //    output_message('alert','You did not provide your password or confirm pass');
        //    $success = 0;
        //}
        if(empty($params['email'])){
            //output_message('alert','You did not provide your email');
            //$success = 0;
            $params['email'] = "";
        }

        if($success!=1) return false;
        //unset($params['sha_pass_hash2']);
        $password = $params['password'];
        unset($params['password']);

        // SRP6 support
        list($salt, $verifier) = getRegistrationData(strtoupper($params['username']), $password);
        unset($params['sha_pass_hash']);
        $params['s'] = $salt;
        $params['v'] = $verifier;
        if (function_exists('spp_db_column_exists') && spp_db_column_exists($this->DB, 'account', 'current_realm')) {
            $params['current_realm'] = max(0, (int)$activeRealmId);
        }

        if ($params['expansion'] == '32')
            $params['expansion'] = '2';
        elseif ($params['expansion'] != '0')
            $params['expansion'] = '1';

        //$params['sha_pass_hash'] = strtoup($this->gethash($params['password']));
        //$params['sha_pass_hash'] = $this->gethash($params['password']);
        $tmp_act_key = '';
        try {
            $setClause = implode(',', array_map(function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; }, array_keys($params)));
            $stmt = $this->DB->prepare("INSERT INTO account SET $setClause");
            $stmt->execute(array_values($params));
            $acc_id = (int)$this->DB->lastInsertId();
            if($acc_id > 0){
                $stmt = $this->DB->prepare("INSERT INTO website_accounts SET account_id=?, registration_ip=?, activation_code=?");
                $stmt->execute([$acc_id, $_SERVER['REMOTE_ADDR'], $tmp_act_key]);
                if (!empty($account_extend) && is_array($account_extend)) {
                    $setClauseAccountExtend = implode(',', array_map(function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; }, array_keys($account_extend)));
                    $stmt = $this->DB->prepare("UPDATE website_accounts SET $setClauseAccountExtend WHERE account_id=? LIMIT 1");
                    $extendValues = array_values($account_extend);
                    $extendValues[] = $acc_id;
                    $stmt->execute($extendValues);
                }
                if((int)spp_config_generic('use_purepass_table', 0)) {
                    $stmt = $this->DB->prepare("INSERT INTO account_pass SET id=?, username=?, password=?, email=?");
                    $stmt->execute([$acc_id, $params['username'], $password, $params['email']]);
                }
                if (function_exists('spp_ensure_account_identity')) {
                    spp_ensure_account_identity(max(1, (int)$activeRealmId), $acc_id, $params['username']);
                }
                return true;
            } else {
                output_message('alert', 'Account creation failed. Please try again.');
                return false;
            }
        } catch (Throwable $e) {
            error_log('[AUTH] Account registration failed: ' . $e->getMessage());
            output_message('alert', 'Account creation failed. Please try again or contact an administrator if the problem continues.');
            return false;
        }
    }

    function isavailableusername($username){
        $stmt = $this->DB->prepare("SELECT count(*) FROM account WHERE username=?");
        $stmt->execute([$username]);
        return (int)$stmt->fetchColumn() < 1;
    }

    function isavailableemail($email){
        $stmt = $this->DB->prepare("SELECT count(*) FROM account WHERE email=?");
        $stmt->execute([$email]);
        return (int)$stmt->fetchColumn() < 1;
    }
    function isvalidemail($email){
        if(preg_match('#^.{1,}@.{2,}\..{2,}$#', $email)==1){
            return true; // email is valid
        }else{
            return false; // email is not valid
        }
    }
    function isvalidregkey($key){
        $stmt = $this->DB->prepare("SELECT count(*) FROM site_regkeys WHERE `key`=?");
        $stmt->execute([$key]);
        return (int)$stmt->fetchColumn() > 0;
    }
    function generate_key()
    {
        $str = microtime(1);
        return sha1(base64_encode(pack("H*", md5(utf8_encode($str)))));
    }
    function delete_key($key){
        $stmt = $this->DB->prepare("DELETE FROM website_regkeys WHERE `key`=?");
        $stmt->execute([$key]);
    }
    function getprofile($acct_id=false){
        $stmt = $this->DB->prepare("
            SELECT * FROM account
            LEFT JOIN website_accounts ON account.id=website_accounts.account_id
            LEFT JOIN website_account_groups ON website_accounts.g_id=website_account_groups.g_id
            WHERE id=?");
        $stmt->execute([(int)$acct_id]);
        return RemoveXSS($stmt->fetch(PDO::FETCH_ASSOC));
    }
    function getgroup($g_id=false){
        $stmt = $this->DB->prepare("SELECT * FROM website_account_groups WHERE g_id=?");
        $stmt->execute([(int)$g_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    function parsesettings($str){
        $set_pre = explode("\n",$str);
        foreach($set_pre as $set_str){$set_str_arr = explode('=',$set_str); $set[$set_str_arr[0]] = $set_str_arr[1]; }
        return $set;
    }
    function getlogin($acct_id=false){
        $stmt = $this->DB->prepare("SELECT username FROM account WHERE id=?");
        $stmt->execute([(int)$acct_id]);
        $res = $stmt->fetchColumn();
        if($res == null) return false;
        return $res;
    }
    function getid($acct_name=false){
        $stmt = $this->DB->prepare("SELECT id FROM account WHERE username=?");
        $stmt->execute([$acct_name]);
        $res = $stmt->fetchColumn();
        if($res == null) return false;
        return $res;
    }
    function gethash($str=false){
        if($str)return sha1(base64_encode(md5(utf8_encode($str)))); // Returns 40 char hash.
        else return false;
    }

    // ONLINE FUNCTIONS //
    function onlinelist_update()  // Updates list & delete old
    {
        $GLOBALS['guests_online']=0;
        $stmt = $this->DB->query("SELECT * FROM `website_online`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $result_row)
        {
            if(time()-$result_row['logged'] <= 60*10)
            {
                if($result_row['user_id']>0){
                  $GLOBALS['users_online'][] = $result_row['user_name'];
                }else{
                  $GLOBALS['guests_online']++;
                }
            }
            else
            {
                $stmt2 = $this->DB->prepare("DELETE FROM `website_online` WHERE `id`=? LIMIT 1");
                $stmt2->execute([$result_row['id']]);
            }
        }
        //db_query("UPDATE `acm_config` SET `val`='".time()."' WHERE `key`='last_onlinelist_update' LIMIT 1");
        // update_settings('last_onlinelist_update',time());
    }

    function onlinelist_add() // Add or update list with new user
    {
        global $user;

        $cur_time = time();
        $stmt = $this->DB->prepare("SELECT count(*) FROM `website_online` WHERE `user_id`=?");
        $stmt->execute([(int)$this->user['id']]);
        if($stmt->fetchColumn() > 0)
        {
            $stmt = $this->DB->prepare("UPDATE `website_online` SET `user_ip`=?,`logged`=?,`currenturl`=? WHERE `user_id`=? LIMIT 1");
            $stmt->execute([$this->user['ip'], $cur_time, $_SERVER['REQUEST_URI'], (int)$this->user['id']]);
        }
        else
        {
            $stmt = $this->DB->prepare("INSERT INTO `website_online` (`user_id`,`user_name`,`user_ip`,`logged`,`currenturl`) VALUES (?,?,?,?,?)");
            $stmt->execute([(int)$this->user['id'], $this->user['username'], $this->user['ip'], $cur_time, $_SERVER['REQUEST_URI']]);
        }
    }

    function onlinelist_del() // Delete user from list
    {
        global $user;
        $stmt = $this->DB->prepare("DELETE FROM `website_online` WHERE `user_id`=? LIMIT 1");
        $stmt->execute([(int)$this->user['id']]);
    }

    function onlinelist_addguest() // Add or update list with new guest
    {
        global $user;

        $cur_time = time();
        $stmt = $this->DB->prepare("SELECT count(*) FROM `website_online` WHERE `user_id`='0' AND `user_ip`=?");
        $stmt->execute([$this->user['ip']]);
        if($stmt->fetchColumn() > 0)
        {
            $stmt = $this->DB->prepare("UPDATE `website_online` SET `user_ip`=?,`logged`=?,`currenturl`=? WHERE `user_id`='0' AND `user_ip`=? LIMIT 1");
            $stmt->execute([$this->user['ip'], $cur_time, $_SERVER['REQUEST_URI'], $this->user['ip']]);
        }
        else
        {
            $stmt = $this->DB->prepare("INSERT INTO `website_online` (`user_ip`,`logged`,`currenturl`) VALUES (?,?,?)");
            $stmt->execute([$this->user['ip'], $cur_time, $_SERVER['REQUEST_URI']]);
        }
    }

    function onlinelist_delguest() // Delete guest from list
    {
        global $user;
        $stmt = $this->DB->prepare("DELETE FROM `website_online` WHERE `user_id`='0' AND `user_ip`=? LIMIT 1");
        $stmt->execute([$this->user['ip']]);
    }
}
?>



