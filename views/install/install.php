<?php
$global_lang = array('en', 'zh-cn', 'zh-hk');

if(isSet($_GET['lang'])) {
	$lang = strtolower($_GET['lang']);
	$_SESSION['lang'] = $lang;
	$_COOKIE["lang"] = $lang;
	setcookie("lang", $lang, time() + (3600 * 24 * 30), '/', getDomain());
	setcookie("lang", $lang, time() + (3600 * 24 * 30), '/');

	if (in_array($lang, $global_lang)) {
		$_COOKIE["lang"] = $lang;
		$_SESSION['lang'] = $lang;
		$lng = $lang;
	} else {
		$lng = 'en';
	}
} else if(isSet($_COOKIE['lang'])) {
	$lang = strtolower($_COOKIE['lang']);
	if (in_array($lang, $global_lang)) {
		$lng = $lang;
	} else {
		$lng = 'en';
	}
} else {
	preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
	$lang = strtolower($matches[1]);
	setcookie("lang", $lang, time() + (3600 * 24 * 30), '/', getDomain());
	setcookie("lang", $lang, time() + (3600 * 24 * 30), '/');
	$_COOKIE["lang"] = $lang;
	
	if (in_array($lang, $global_lang)) {
		$_COOKIE["lang"] = $lang;
		$_SESSION['lang'] = $lang;
		$lng = $lang;
	} else {
		$lng = 'en';
	}
}

function getDomain() {
	$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI'];

	$pieces = parse_url($link);
	$domain = isset($pieces['host']) ? $pieces['host'] : '';
	if(preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)){
		return '.'.$regs['domain'];
	}
	return FALSE;
}

$trans = array (
	'en' => array (
		'setup_title'    => 'Install Flarum',
    'setup_description'     => 'Set up your forum by filling out your details below. If you have any trouble, get help on the <a href="https://flarum.org/docs/install.html" target="_blank">Flarum website</a>.',
    'forum_title_label'     => 'Forum Title',
    'mysql_host_label'    => 'MySQL Host',
    'mysql_database_label' => 'MySQL Database',
    'mysql_username_label'    => 'MySQL Username',
    'mysql_password_label'    => 'MySQL Password',
    'table_prefix_label'    => 'Table Prefix',
    'admin_username_label'          => 'Admin Username',
    'admin_email_label'    => 'Admin Email',
    'admin_password_label'     => 'Admin Password',
    'confirm_password_label'     => 'Confirm Password',
    'install_flarum_label'    => 'Install Flarum',
    'wait_label'    => 'Please Wait...',
    'went_wrong_label'    => 'Something went wrong:',
    'problems_hold_up'    => 'Hold Up!',
    'problems_description'    => 'These problems must be resolved before you can continue the installation. If you\'re having trouble, get help on the <a href="https://flarum.org/docs/install.html" target="_blank">Flarum website</a>.',
    'update_title'    => 'Update Flarum',
    'update_description'    => 'Enter your database password to update Flarum. Before you proceed, you should <strong>back up your database</strong>. If you have any trouble, get help on the <a href="http://flarum.org/docs/update.html" target="_blank">Flarum website</a>.'),
	'zh-cn' => array (
		'setup_title'    => '安装 Flarum',
    'setup_description'     => '填写信息完成论坛安装。如果您遇到问题，请<a href="https://docs.flarum.org/zh/install.html" target="_blank">查阅我们的文档</a>。',
    'forum_title_label'     => '论坛标题',
    'mysql_host_label'    => 'MySQL 主机',
    'mysql_database_label' => 'MySQL 数据库',
    'mysql_username_label'    => 'MySQL 用户名',
    'mysql_password_label'    => 'MySQL 密码',
    'table_prefix_label'    => '表前缀',
    'admin_username_label'          => '管理员用户名',
    'admin_email_label'    => '管理员邮箱',
    'admin_password_label'     => '管理员密码',
    'confirm_password_label'     => '确认密码',
    'wait_label'    => '请稍后...',
    'went_wrong_label'    => '出了点问题：',
    'problems_hold_up'    => '等等！',
    'problems_description'    => '必须解决以下问题才能继续安装。如果您遇到困难，请查阅<a href="https://docs.flarum.org/zh/install.html" target="_blank">Flarum 文档</a>获得帮助。',
    'update_title'    => '更新 Flarum',
    'update_description'    => '请输入数据库密码。在继续操作前，您应该<strong>备份数据库</strong>。如果您遇到问题，请查阅<a href="http://docs.flarum.org/zh/update.html" target="_blank">更新手册</a>。'),
  'zh-hk' => array (
    'setup_title'    => '安裝 Flarum',
    'setup_description'     => '填寫信息完成論壇安裝。如果您遇到問題，請<a href="https://docs.flarum.org/zh/install.html" target="_blank">查閱我們的文檔</a>。',
    'forum_title_label'     => '論壇標題',
    'mysql_host_label'    => 'MySQL 主機',
    'mysql_database_label' => 'MySQL 數據庫',
    'mysql_username_label'    => 'MySQL 用戶名',
    'mysql_password_label'    => 'MySQL 密碼',
    'table_prefix_label'    => '表前綴',
    'admin_username_label'          => '管理員用戶名',
    'admin_email_label'    => '管理員郵箱',
    'admin_password_label'     => '管理員密碼',
    'confirm_password_label'     => '確認密碼',
    'wait_label'    => '請稍後...',
    'went_wrong_label'    => '出了點問題：',
    'problems_hold_up'    => '等等！',
    'problems_description'    => '必須解決以下問題才能繼續安裝。如果您遇到困難，請查閱<a href="https://docs.flarum.org/zh/install.html" target="_blank">Flarum 文檔</a>獲得幫助。',
    'update_title'    => '更新 Flarum',
    'update_description'    => '請輸入數據庫密碼。在繼續操作前，您應該<strong>備份數據庫</strong>。如果您遇到問題，請查閱<a href="http://docs.flarum.org/zh/update.html" target="_blank">更新手冊</a>。')
);
?>

<h2><?php echo $trans[$lng]['setup_title'] ?></h2>

<p><?php echo $trans[$lng]['setup_description'] ?></p>

<form method="post">
  <div id="error" style="display:none"></div>

  <div class="FormGroup">
    <div class="FormField">
      <label><?php echo $trans[$lng]['forum_title_label'] ?></label>
      <input name="forumTitle">
    </div>
  </div>
  
  <div class="FormGroup">
    <div class="FormField">
      <label><?php echo $trans[$lng]['mysql_host_label'] ?></label>
      <input name="mysqlHost" value="localhost">
    </div>

    <div class="FormField">
      <label><?php echo $trans[$lng]['mysql_database_label'] ?></label>
      <input name="mysqlDatabase">
    </div>

    <div class="FormField">
      <label><?php echo $trans[$lng]['mysql_username_label'] ?></label>
      <input name="mysqlUsername">
    </div>

    <div class="FormField">
      <label><?php echo $trans[$lng]['mysql_password_label'] ?></label>
      <input type="password" name="mysqlPassword">
    </div>

    <div class="FormField">
      <label><?php echo $trans[$lng]['table_prefix_label'] ?></label>
      <input type="text" name="tablePrefix">
    </div>
  </div>

  <div class="FormGroup">
    <div class="FormField">
      <label><?php echo $trans[$lng]['admin_username_label'] ?></label>
      <input name="adminUsername">
    </div>

    <div class="FormField">
      <label><?php echo $trans[$lng]['admin_email_label'] ?></label>
      <input name="adminEmail">
    </div>

    <div class="FormField">
      <label><?php echo $trans[$lng]['admin_password_label'] ?></label>
      <input type="password" name="adminPassword">
    </div>

    <div class="FormField">
      <label><?php echo $trans[$lng]['confirm_password_label'] ?></label>
      <input type="password" name="adminPasswordConfirmation">
    </div>
  </div>

  <div class="FormButtons">
    <button type="submit"><?php echo $trans[$lng]['setup_title'] ?></button>
  </div>
</form>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script>
$(function() {
  $('form :input:first').select();

  $('form').on('submit', function(e) {
    e.preventDefault();

    var $button = $(this).find('button')
      .text('<?php echo $trans[$lng]['wait_label'] ?>')
      .prop('disabled', true);

    $.post('', $(this).serialize())
      .done(function() {
        window.location.reload();
      })
      .fail(function(data) {
        $('#error').show().text('<?php echo $trans[$lng]['went_wrong_label'] ?>\n\n' + data.responseText);

        $button.prop('disabled', false).text('<?php echo $trans[$lng]['setup_title'] ?>');
      });

    return false;
  });
});
</script>