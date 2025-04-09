<?php
if (!defined('APP_LOADED')) die('Direct file access is not allowed');
@session_start();
$POST = SAP::getInstance('POST');
$GET = SAP::getInstance('GET');
if ($GET->logout){
    $_SESSION['cxc-user'] = '';
    $_SESSION['cxc-user-folder'] = '';
    $_SESSION['cxc-user-csrf'] = '';
    Header('Location: '. LOCUS);
    die();
}
if ($POST->login_name) {
    $POST->login_name = strtolower($POST->login_name);
    if ($POST->login_name == 'al'){
        $_SESSION['cxc-user'] = 'AL';
        $_SESSION['cxc-user-folder'] = 'al';
    }
    if ($POST->login_name == 'hm'){
        $_SESSION['cxc-user'] = 'HM';
        $_SESSION['cxc-user-folder'] = 'hm';
    }
    if ($POST->login_name == 'se'){
        $_SESSION['cxc-user'] = 'SE';
        $_SESSION['cxc-user-folder'] = 'se';
    }
    if ($POST->login_name == 'df'){
        $_SESSION['cxc-user'] = 'DF';
        $_SESSION['cxc-user-folder'] = 'df';
    }
    if ($POST->login_name == 'jp'){
        $_SESSION['cxc-user'] = 'JP';
        $_SESSION['cxc-user-folder'] = 'jp';
    }
    Header('Location: '. LOCUS);
    die();
}
if (!empty($_SESSION['cxc-user'])){
    if (empty($_SESSION['cxc-user-folder'])){
        $_SESSION['cxc-user-folder'] = substr(strtolower($_SESSION['cxc-user']),0,3);
    }
    if (empty($_SESSION['cxc-user-csrf'])){
        $_SESSION['cxc-user-csrf'] = md5(time());
    }
    defined('USER_DIR') OR define('USER_DIR', __DIR__ . DIRECTORY_SEPARATOR . $_SESSION['cxc-user-folder'] );
    defined('DATA_DIR') OR define('DATA_DIR', USER_DIR .DIRECTORY_SEPARATOR.'d');
    defined('FINAL_DIR') OR define('FINAL_DIR', USER_DIR. DIRECTORY_SEPARATOR.'f');
    defined('SEQ_DIR') OR define('SEQ_DIR', USER_DIR.DIRECTORY_SEPARATOR. 'f');
    @mkdir(USER_DIR);
    @mkdir(DATA_DIR);
    @mkdir(FINAL_DIR);
    @mkdir(SEQ_DIR);
    return;
}

$RHTML = SAP::getInstance('HTML');
$RHTML->html()->class('no-js');

$head = $RHTML->html()->head();
$head->Append('<script>(function(e,t,n){var r=e.querySelectorAll("html")[0];r.className=r.className.replace(/(^|\s)no-js(\s|$)/,"$1js$2")})(document,window,0);</script>');

$body = $RHTML->body();



$body->append('
<form class="box" method="post">
  <div class="box__input">
    <input class="box__file" type="text" name="login_name" maxlength=2 list="login_name_dl" required />
    
  </div>
  <button class="btn" type="submit">Go</button>
  <datalist id="login_name_dl">
  <option value="al">Ledgister</option>
  <option value="se">Edwards</option>
  <option value="hm">Morant</option>
  <option value="df">Forbes</option>
  <option value="jp">Patience</option>
  </datalist>
</form>');

$body->create('style')->append(file_get_contents('style.css'));
$body->append("<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>");
$body->create('script')->Append(file_get_contents('js.js'));
$RHTML->send();
die();
