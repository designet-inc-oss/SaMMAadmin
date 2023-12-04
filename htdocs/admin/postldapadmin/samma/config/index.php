<?php

/*
 * postLDAPadmin
 *
 * Copyright (C) 2006,2007 DesigNET, INC.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

/***********************************************************
 * SaMMA設定ファイル編集画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.16 $
 * $Date: 2013/09/03 03:11:11 $
 **********************************************************/

include_once("../../initial");
include_once("lib/dglibpostldapadmin");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibldap");
include_once("lib/dglibsamma");

/********************************************************
各ページ毎の設定
*********************************************************/

define("OPERATION", "設定ファイル編集");
define("TMPLFILE", "samma/samma_admin_config.tmpl");

/***********************************************************
 * display_result()
 *
 * 置き換えタグの情報をセットする
 *
 * [引数]
 *      $tag  置き換えタグの配列
 *
 * [返り値]
 *       なし
 **********************************************************/
function display_result(&$tag) {
    global $sesskey;
    global $samma_conf;
    global $logfacility;
    global $db_types;
    global $str_code;
    global $web_conf; 

    /* パスワード */
    $commandpass = "";
    if (isset($samma_conf["commandpass"]) === TRUE && 
              $samma_conf["commandpass"] != "") {
        $commandpass = escape_html($samma_conf["commandpass"]);
    }
    $tag["<<COMMANDPASS>>"] = $commandpass;

    /* ログファシリティ */
    $html_log = "";
    foreach ($logfacility as $log) {
        $selected = "";
        /* 一致したら選択 */
        if (isset($samma_conf["syslogfacility"]) === TRUE &&
                  $samma_conf["syslogfacility"] != "") {
            if ($log == $samma_conf["syslogfacility"]) {
                $selected = "selected";
            } 
        } else {
            /* セットされていない場合はデフォルト */
            if ($log == "local0") {
                $selected = "selected";
            }
        }
        $html_log .= "<option value=\"$log\" $selected>$log";
        $tag["<<HTML_LOG>>"] = $html_log;
    }

    /* 一時格納ディレクトリ */
    $encryptiontmpdir = "";
    if (isset($samma_conf["encryptiontmpdir"]) === TRUE && 
              $samma_conf["encryptiontmpdir"] != "") {
        $encryptiontmpdir = escape_html($samma_conf["encryptiontmpdir"]);
    }
    $tag["<<ENCRYPTIONTMPDIR>>"] = $encryptiontmpdir;

    /* zipコマンドパス */
    $zipcommand = "";
    if (isset($samma_conf["zipcommand"]) === TRUE && 
              $samma_conf["zipcommand"] != "") {
        $zipcommand = escape_html($samma_conf["zipcommand"]);
    }
    $tag["<<ZIPCOMMAND>>"] = $zipcommand;

    /* zipコマンドオプション */
    $zipcommandopt = "";
    if (isset($samma_conf["zipcommandopt"]) === TRUE && 
              $samma_conf["zipcommandopt"] != "") {
        $zipcommandopt = escape_html($samma_conf["zipcommandopt"]);
    }
    $tag["<<ZIPCOMMANDOPT>>"] = $zipcommandopt;

    /* 内部ドメインDB形式/パス */
   $html_sender = "";
    foreach ($db_types as $s_type) {
        $s_file = "";
        $type = "";
        if (isset($samma_conf["senderdb"]) === TRUE &&
                  $samma_conf["senderdb"] != "") {
            $send = explode(":", $samma_conf["senderdb"], 2);
            $type = $send[0];
            $s_file = escape_html($send[1]);
        }
        $selected = "";
        /* 一致したら選択 */
        if ($s_type == $type) {
                $selected = "selected";
        }
        $html_sender .= "<option value=\"$s_type\" $selected>$s_type";
        $tag["<<HTML_SENDER>>"] = $html_sender;
    }
    $tag["<<S_FILE>>"] = $s_file;
    
    /* 受信者DB形式/パス */
    $html_rcpt = "";
    foreach ($db_types as $r_type) {
        $r_file = "";
        $type = "";
        if (isset($samma_conf["rcptdb"]) === TRUE &&
                  $samma_conf["rcptdb"] != "") {
            $rcpt = explode(":", $samma_conf["rcptdb"], 2);
            $type = $rcpt[0];
            $r_file = escape_html($rcpt[1]);
        }
        $selected = "";
        /* 一致したら選択 */
        if ($r_type == $type) {
                $selected = "selected";
        }
        $html_rcpt .= "<option value=\"$r_type\" $selected>$r_type";
        $tag["<<HTML_RCPT>>"] = $html_rcpt;
    }
    $tag["<<R_FILE>>"] = $r_file;
        
    /* 通知メールテンプレートパス */
    $templatepath = "";
    if (isset($samma_conf["templatepath"]) === TRUE && 
              $samma_conf["templatepath"] != "") {
        $templatepath = escape_html($samma_conf["templatepath"]);
    }
    $tag["<<TEMPLATEPATH>>"] = $templatepath;


    /* 送信先通知メールテンプレートパス */
    $rcpttemplatepath = "";
    if (isset($samma_conf["rcpttemplatepath"]) === TRUE &&
              $samma_conf["rcpttemplatepath"] != "") {
        $rcpttemplatepath = escape_html($samma_conf["rcpttemplatepath"]);
    }
    $tag["<<RCPTTEMPLATEPATH>>"] = $rcpttemplatepath;

    /* sendmailコマンドパス/オプション */
    $sendmailcommand = "";
    if (isset($samma_conf["sendmailcommand"]) === TRUE && 
              $samma_conf["sendmailcommand"] != "") {
        $sendmailcommand = escape_html($samma_conf["sendmailcommand"]);
    }
    $tag["<<SENDMAILCOMMAND>>"] = $sendmailcommand;

    /* 暗号化ZIPファイルフォーマット */
    $zipfilename = "";
    if (isset($samma_conf["zipfilename"]) === TRUE && 
              $samma_conf["zipfilename"] != "") {
        $zipfilename = escape_html($samma_conf["zipfilename"]);
    }
    $tag["<<ZIPFILENAME>>"] = $zipfilename;

    /* 一時保存ディレクトリ */
    $mailsavetmpdir = "";
    if (isset($samma_conf["mailsavetmpdir"]) === TRUE && 
              $samma_conf["mailsavetmpdir"] != "") {
        $mailsavetmpdir = escape_html($samma_conf["mailsavetmpdir"]);
    }
    $tag["<<MAILSAVETMPDIR>>"] = $mailsavetmpdir;

    /* パスワード文字数 */
    $passwordlength = "";
    if (isset($samma_conf["passwordlength"]) === TRUE && 
              $samma_conf["passwordlength"] != "") {
        $passwordlength = escape_html($samma_conf["passwordlength"]);
    }
    $tag["<<PASSWORDLENGTH>>"] = $passwordlength;

    /* ファイル名変換文字コード */
    $html_strcode = "";
    foreach ($str_code as $code) {
        $selected = "";
        /* 一致したら選択 */
        if (isset($samma_conf["strcode"]) === TRUE &&
              $samma_conf["strcode"] != "") {
            if ($code == $samma_conf["strcode"]) {
                $selected = "selected";
            } 
        } else {
            /* セットされていない場合はデフォルト */
            if ($code == DEF_CODE) {
                $selected = "selected";
            }
        }
        $html_strcode .= "<option value=\"$code\" $selected>$code";
        $tag["<<HTML_STRCODE>>"] = $html_strcode;
    }

    /* デフォルト処理 */
    $enc_on = "";
    $enc_off = "checked";
    if (isset($samma_conf["defaultencryption"]) === TRUE && 
              $samma_conf["defaultencryption"] != "") {
        if ($samma_conf["defaultencryption"] == "yes") {
            $enc_on = "checked";
            $enc_off = "";
        }
    }
    $tag["<<ENC_ON>>"] = $enc_on;
    $tag["<<ENC_OFF>>"] = $enc_off;

    /* デフォルトパスワード */
    $defaultpassword = "";
    if (isset($samma_conf["defaultpassword"]) === TRUE && 
              $samma_conf["defaultpassword"] != "") {
        $defaultpassword = escape_html($samma_conf["defaultpassword"]);
    }
    $tag["<<DEFAULTPASSWORD>>"] = $defaultpassword;

    /* ユーザ個別設定 */
    $user_on = "";
    $user_off = "checked";
    $disabled = "disabled";
    if (isset($samma_conf["userpolicy"]) === TRUE && 
              $samma_conf["userpolicy"] != "") {
        if ($samma_conf["userpolicy"] == "yes") {
            $user_on = "checked";
            $user_off = "";
            $disabled = "";
        }
    }
    $tag["<<USER_ON>>"] = $user_on;
    $tag["<<USER_OFF>>"] = $user_off;
    $tag["<<DISABLED>>"] = $disabled;

    /* LDAPサーバ・ポート */
    $ldapserver = "";
    $ldapport = "";
    if (isset($samma_conf["ldapuri"]) === TRUE && 
              $samma_conf["ldapuri"] != "") {
        /* 「://」で切り分け */
        $uri = explode("://", $samma_conf["ldapuri"]);
        /* IPとポートに切り分け */
        $serverdata = explode(":", $uri[1]);
        /* 格納(ポートは末尾の「/」を取る) */
        $ldapserver = escape_html($serverdata[0]);
        $ldapport = escape_html(rtrim($serverdata[1], "/"));
    }
    $tag["<<LDAPSERVER>>"] = $ldapserver;
    $tag["<<LDAPPORT>>"] = $ldapport;

    /* LDAPベースDN */
    $ldapbasedn = "";
    if (isset($samma_conf["ldapbasedn"]) === TRUE && 
              $samma_conf["ldapbasedn"] != "") {
        $ldapbasedn = escape_html($samma_conf["ldapbasedn"]);
    }
    $tag["<<LDAPBASEDN>>"] = $ldapbasedn;

    /* LDAPバインドDN */
    $ldapbinddn = "";
    if (isset($samma_conf["ldapbinddn"]) === TRUE && 
              $samma_conf["ldapbinddn"] != "") {
        $ldapbinddn = escape_html($samma_conf["ldapbinddn"]);
    }
    $tag["<<LDAPBINDDN>>"] = $ldapbinddn;

    /* LDAPバインドパスワード */
    $ldapbindpassword = "";
    if (isset($samma_conf["ldapbindpassword"]) === TRUE && 
              $samma_conf["ldapbindpassword"] != "") {
        $ldapbindpassword = escape_html($samma_conf["ldapbindpassword"]);
    }
    $tag["<<LDAPBINDPASSWORD>>"] = $ldapbindpassword;

    /* LDAPフィルタ */
    $ldapfilter = "";
    if (isset($samma_conf["ldapfilter"]) === TRUE && 
              $samma_conf["ldapfilter"] != "") {
        $ldapfilter = escape_html($samma_conf["ldapfilter"]);
    }
    $tag["<<LDAPFILTER>>"] = $ldapfilter;

    /* 拡張子DB形式/パス */
    $html_extension = "";
    foreach ($db_types as $e_type) {
        $e_file = "";
        $type = "";
        if (isset($samma_conf["extensiondb"]) === TRUE &&
            $samma_conf["extensiondb"] != "") {
            $extension = explode(":", $samma_conf["extensiondb"], 2);
            $type = $extension[0];
            $e_file = escape_html($extension[1]);
        }
        $selected = "";
        /* 一致したら選択 */
        if ($e_type == $type) {
            $selected = "selected";
        }
        $html_extension .= "<option value=\"$e_type\" $selected>$e_type";
        $tag["<<HTML_EXTENSION>>"] = $html_extension;

    }
    $tag["<<E_FILE>>"] = $e_file;

    /* コマンドDB形式/パス */
    $html_command = "";
    foreach ($db_types as $e_type) {
        $com_file = "";
        $type = "";
        if (isset($samma_conf["commanddb"]) === TRUE &&
            $samma_conf["commanddb"] != "") {
            $command = explode(":", $samma_conf["commanddb"], 2);
            $type = $command[0];
            $com_file = escape_html($command[1]);
        }
        $selected = "";
        /* 一致したら選択 */
        if ($e_type == $type) {
            $selected = "selected";
        }
        $html_command .= "<option value=\"$e_type\" $selected>$e_type";
        $tag["<<HTML_COMMAND>>"] = $html_command;

    }
    $tag["<<COM_FILE>>"] = $com_file;


    /* 宛先にパスワード通知設定 */
    $passnotice_disabled = "disabled";
    $passnotice_sender = "";
    $passnotice_rcpt = "";
    $passnotice_both = "";
    
    /* 宛先にパスワード通知機能が有効になっている場合 */
    if (is_active_plugin("pluginpassnoticeactive")) {

        $passnotice_disabled = "";
 
        /* passwordnotice項目がセットしている場合 */
        if (isset($samma_conf["passwordnotice"])) {
            if ($samma_conf["passwordnotice"] == "0") {
                $passnotice_sender = "checked";
            } elseif ($samma_conf["passwordnotice"] == "1") {
                $passnotice_rcpt = "checked";
            } elseif ($samma_conf["passwordnotice"] == "2") {
                $passnotice_both = "checked";
            } else {
                $passnotice_sender = "checked";
            }
        } else {
            $passnotice_sender = "checked";
        }
    }

    $tag["<<PLUGINPASSNOTICEACTIVE>>"] = $passnotice_disabled;
    $tag["<<PASSNOTICE_SENDER>>"] = $passnotice_sender;
    $tag["<<PASSNOTICE_RCPT>>"] = $passnotice_rcpt;
    $tag["<<PASSNOTICE_BOTH>>"] = $passnotice_both;

    /* subjectsw追加機能 */
    $output_pass_log = "出力しない";
    $subjectsw_disable = "disabled";
    $useaddmessageheader_on = "";
    $useaddmessageheader_off = "checked";
    $messagetmpljppath = "";
    $messagetmplenpath = "";
    $messagetmplbothpath = "";
    $useencryptsubject_on = "";
    $useencryptsubject_off = "checked";
    $subjectencryptstringjp = "";
    $subjectencryptstringen = "";

    /* subjectsw追加機能が有効になっている場合 */
    if (is_active_plugin("pluginsubjectswactive")) {

        $output_pass_log = "出力する";
        $subjectsw_disable = "";
 
        /* メール本文に定型文を挿入する設定 */
        if (isset($samma_conf["useaddmessageheader"])) {
            if ($samma_conf["useaddmessageheader"] === "yes") {
                $useaddmessageheader_on = "checked";
                $useaddmessageheader_off = "";
            }
        }

        /* 本文追記のファイルパス(日本語) */
        if (isset($samma_conf["messagetmpljppath"])) {
            $messagetmpljppath = escape_html($samma_conf["messagetmpljppath"]);
        }

        /* 本文追記のファイルパス(英語) */
        if (isset($samma_conf["messagetmplenpath"])) {
            $messagetmplenpath = escape_html($samma_conf["messagetmplenpath"]);
        }

        /* 本文追記のファイルパス(両方) */
        if (isset($samma_conf["messagetmplbothpath"])) {
            $messagetmplbothpath = escape_html($samma_conf["messagetmplbothpath"]);
        }

        /* 件名判定暗号化の設定 */
        if (isset($samma_conf["useencryptsubject"])) {
            if ($samma_conf["useencryptsubject"] === "yes") {
                $useencryptsubject_on = "checked";
                $useencryptsubject_off = "";
            }
        }

        /* 件名判定文字列(日本語) */
        if (isset($samma_conf["subjectencryptstringjp"])) {
            $subjectencryptstringjp = escape_html($samma_conf["subjectencryptstringjp"]);
        }

        /* 件名判定文字列(英語) */
        if (isset($samma_conf["subjectencryptstringen"])) {
            $subjectencryptstringen = escape_html($samma_conf["subjectencryptstringen"]);
        }
    }

    $tag["<<PLUGINSUBJECTSWACTIVE>>"] = $subjectsw_disable;
    $tag["<<OUTPUTPASSTOLOG>>"] = $output_pass_log;
    $tag["<<USERADDMESSAGEHEADER_ON>>"] = $useaddmessageheader_on;
    $tag["<<USERADDMESSAGEHEADER_OFF>>"] = $useaddmessageheader_off;
    $tag["<<MESSAGETMPLJPPATH>>"] = $messagetmpljppath;
    $tag["<<MESSAGETMPLENPATH>>"] = $messagetmplenpath;
    $tag["<<MESSAGETMPLBOTHPATH>>"] = $messagetmplbothpath;
    $tag["<<USERENCRYPTSUBJECT_ON>>"] = $useencryptsubject_on;
    $tag["<<USERENCRYPTSUBJECT_OFF>>"] = $useencryptsubject_off;
    $tag["<<SUBJECTENCRYPTSTRINGJP>>"] = $subjectencryptstringjp;
    $tag["<<SUBJECTENCRYPTSTRINGEND>>"] = $subjectencryptstringen;

    /* 安全化した添付ファイルのContent-Type */
    $zipattachmentcontenttype = "";
    if (isset($samma_conf["zipattachmentcontenttype"]) === TRUE &&
              $samma_conf["zipattachmentcontenttype"] != "") {
        $zipattachmentcontenttype = escape_html($samma_conf["zipattachmentcontenttype"]);
    }
    $tag["<<ZIP_ATTCHMENT_CONTENT_TYPE>>"] = $zipattachmentcontenttype;
    
    return TRUE;
}

/*********************************************************
 * check_conf_data
 *
 * 設定ファイルデータチェック
 *
 * [引数]
 *       $data		設定ファイルデータ
 *
 * [返り値]
 *	TRUE		正常
 *	FALSE		異常
 **********************************************************/
function check_conf_data(&$data)
{
    global $err_msg;
    global $logfacility;
    global $str_code;
    global $samma_conf;

    /* 外部連携用パスワード */
    /* 空チェック */
    if ($data["commandpass"] == "") {
        $err_msg = "外部連携用パスワードが入力されていません。";
        return FALSE;
    }
    /* 形式チェック　*/
    if (check_samma_pass($data["commandpass"]) !== TRUE) {
        $err_msg = "外部連携用" . $err_msg;
        return FALSE;
    }


    /* syslogファシリティ */
    foreach ($logfacility as $log) {
        if ($log == $data["syslogfacility"]) {
            $data["syslogfacility"] = $log;
        }
    }


    /* 一時格納ディレクトリ */
    /* 空チェック */
    if ($data["encryptiontmpdir"] == "") {
        $err_msg = "一時格納ディレクトリが入力されていません。";
        return FALSE;
    }
    /* 形式チェック　*/
    if (check_dir($data["encryptiontmpdir"]) !== TRUE) {
        $err_msg = "一時格納" . $err_msg;
        return FALSE;
    }


    /* zipコマンドパス */
    /* 空チェック */
    if ($data["zipcommand"] == "") {
        $err_msg = "安全化コマンドパスが入力されていません。";
        return FALSE;
    }
    /* 形式チェック　*/
    if (check_command($data["zipcommand"]) !== TRUE) {
        $err_msg = "zip" . $err_msg;
        return FALSE;
    }


    /* zipコマンドオプション */
    /* 空チェック */
    if ($data["zipcommandopt"] != "") {
        /* 形式チェック　*/
        if (check_str($data["zipcommandopt"], CHECK_STR4) !== TRUE) {
            $err_msg = "安全化コマンドオプションの" . $err_msg;
            return FALSE;
        }
    }


    /* 内部ドメインDB形式/パス */
    /* 空チェック */
    if ($data["sd_dbfile"] == "") {
        $data["senderdb"] = "";
    } else {
        /* db名チェック(".db"がついているか) */
        if (substr($data["sd_dbfile"], -3, 3) != ".db") {
            $data["sd_dbfile"] .= ".db";
            $data["senderdb"] .= ".db";
        }
        /* 形式チェック(なければ作成) */
        if (check_db($data["sd_dbfile"], $data["sd_dbtype"]) === FALSE) {
            $err_msg = "内部ドメイン" . $err_msg;
            return FALSE;
        }
    }


    /* 受信者DB形式/パス */
    /* 空チェック */
    if ($data["rp_dbfile"] == "") {
        $data["rcptdb"] = "";
    } else {
        /* db名チェック(".db"がついているか) */
        if (substr($data["rp_dbfile"], -3, 3) != ".db") {
            $data["rp_dbfile"] .= ".db";
            $data["rcptdb"] .= ".db";
        }

        /* 形式チェック(なければ作成) */
        if (check_db($data["rp_dbfile"], $data["rp_dbtype"]) === FALSE) {
            $err_msg = "受信者" . $err_msg;
            return FALSE;
        }
    }

    /* テンプレート */
    /* 空チェック */
    if ($data["templatepath"] == "") {
        $err_msg = "通知メールテンプレートパスが入力されていません。";
        return FALSE;
    }
    /* 形式チェック(なければ作成) */
    if (check_tmpl($data["templatepath"], DEF_TMPL) === FALSE) {
        return FALSE;
    }

    /* 送信先テンプレート */
    if ($data["rcpttemplatepath"] !== "") {
        /* 形式チェック(なければ作成) */
        if (check_tmpl($data["rcpttemplatepath"], DEF_RCPT_TMPL) === FALSE) {
            return FALSE;
        }
    }

    /* sendmailコマンドパス/オプション */
    /* 空チェック */
    if ($data["sendmailcommand"] == "") {
        $err_msg = "sendmailコマンドパス/オプションが入力されていません。";
        return FALSE;
    }
    /* 形式チェック　*/
    if (check_str($data["sendmailcommand"], CHECK_STR4) !== TRUE) {
        $err_msg = "sendmailコマンドパス/オプションの" . $err_msg;
        return FALSE;
    }
    /* コマンドチェック */
    $com = explode(" ", $data["sendmailcommand"], 2); 
    if (check_command($com[0]) !== TRUE) {
        $err_msg = "sendmail" . $err_msg;
        return FALSE;
    }


    /* 暗号化ZIPファイルフォーマット */
    /* 空チェック */
    if ($data["zipfilename"] == "") {
        $err_msg = "受信者に届くメールの添付ファイル名フォーマットが入力されていません。";
        return FALSE;
    }
    /* 形式チェック　*/
    if (check_str($data["zipfilename"], CHECK_STR3) !== TRUE) {
        $err_msg = "安全化コマンドオプションの" . $err_msg;
        return FALSE;
    }


    /* 一時保存ディレクトリ */
    /* 空チェック */
    if ($data["mailsavetmpdir"] == "") {
        $err_msg = "一時保存ディレクトリが入力されていません。";
        return FALSE;
    }
    /* 形式チェック　*/
    if (check_dir($data["mailsavetmpdir"]) !== TRUE) {
        $err_msg = "一時保存" . $err_msg;
        return FALSE;
    }


    /* パスワード文字数 */
    if ($data["passwordlength"] != "") {
        if (is_numeric($data["passwordlength"]) === FALSE) {
            $err_msg = "パスワード文字数の形式が不正です。";
            return FALSE;
        } elseif ($data["passwordlength"] < PASS_MIN || $data["passwordlength"] > PASS_MAX) {
            $err_msg = "パスワード文字数の形式が不正です。";
            return FALSE;
        }
    }

    /* ファイル名変換文字コード */
    foreach ($str_code as $code) {
        if ($code == $data["strcode"]) {
            $data["strcode"] = $code;
        }
    }


    /* デフォルトパスワード */
    /* 空チェック */
    if ($data["defaultpassword"] != "") {
        /* 形式チェック　*/
        if (check_samma_pass($data["defaultpassword"]) !== TRUE) {
            $err_msg = "デフォルト" . $err_msg;
            return FALSE;
        }
    }

    /* ユーザ個別設定が有効の時 */
    if ($data["userpolicy"] == "yes") {

        /* LDAPサーバ */
        /* 空チェック */
        if ($data["ldapserver"] == "") {
            $err_msg = "LDAPサーバが入力されていません。";
            return FALSE;
        }
        /* 形式チェック　*/
        if (check_ip_addr($data["ldapserver"]) !== TRUE) {
            $err_msg = "LDAPサーバの" . $err_msg;
            return FALSE;
        }


        /* LDAPポート */
        /* 空チェック */
        if ($data["ldapport"] == "") {
            $err_msg = "LDAPポートが入力されていません。";
            return FALSE;
        }
        /* 形式チェック　*/
        if (check_port($data["ldapport"]) === FALSE) {
            $err_msg = "LDAPポートの形式が不正です。";
            return FALSE;
        }


        /* LDAPベースDN */
        /* 空チェック */
        if ($data["ldapbasedn"] == "") {
            $err_msg = "LDAPベースDNが入力されていません。";
            return FALSE;
        }
        /* 形式チェック　*/
        if (check_str($data["ldapbasedn"], CHECK_STR6) !== TRUE) {
            $err_msg = "LDAPベースDNの" . $err_msg;
            return FALSE;
        }


        /* LDAPバインドDN */
        /* 空チェック */
        if ($data["ldapbinddn"] == "") {
            $err_msg = "LDAPバインドDNが入力されていません。";
            return FALSE;
        }
        /* 形式チェック　*/
        if (check_str($data["ldapbinddn"], CHECK_STR6) !== TRUE) {
            $err_msg = "LDAPバインドDNの" . $err_msg;
            return FALSE;
        }


        /* LDAPバインドパスワード */
        /* 空チェック */
        if ($data["ldapbindpassword"] == "") {
            $err_msg = "LDAPバインドパスワードが入力されていません。";
            return FALSE;
        }
        /* 形式チェック　*/
        if (check_samma_pass($data["ldapbindpassword"]) !== TRUE) {
            $err_msg = "LDAPバインド" . $err_msg;
            return FALSE;
        }


        /* LDAPフィルタ */
        /* 空チェック */
        if ($data["ldapfilter"] == "") {
            $err_msg = "LDAPフィルタが入力されていません。";
            return FALSE;
        }
        /* 形式チェック　*/
        if (check_str($data["ldapfilter"], CHECK_STR3, FILTER_MIN, FILTER_MAX) !== TRUE) {
            $err_msg = "LDAPフィルタの" . $err_msg;
            return FALSE;
        }
    }

    /* 拡張子DB形式/パス */
    /* 空チェック */
    if ($data["ex_dbfile"] == "") {
        $data["extensiondb"] = "";
    } else {
        /* db名チェック(".db"がついているか) */
        if (substr($data["ex_dbfile"], -3, 3) != ".db") {
            $data["ex_dbfile"] .= ".db";
            $data["extensiondb"] .= ".db";
        }

        /* 形式チェック(なければ作成) */
        if (check_db($data["ex_dbfile"], $data["ex_dbtype"]) === FALSE) {
            $err_msg = "拡張子" . $err_msg;
            return FALSE;
        }
    }

    /* コマンドDB形式/パス */
    /* 空チェック */
    if ($data["com_dbfile"] == "") {
        $data["commanddb"] = "";
    } else {
        /* db名チェック(".db"がついているか) */
        if (substr($data["com_dbfile"], -3, 3) != ".db") {
            $data["com_dbfile"] .= ".db";
            $data["commanddb"] .= ".db";
        }

        /* 形式チェック(なければ作成) */
        if (check_db($data["com_dbfile"], $data["com_dbtype"]) === FALSE) {
            $err_msg = "コマンド" . $err_msg;
            return FALSE;
        }
    }

    /* 宛先にパスワードを通知する機能が有効になっている場合 */
    if (is_active_plugin("pluginpassnoticeactive")) {
        /* パスワード通知の設定 */
        if ($data["passwordnotice"] !== "0" && 
            $data["passwordnotice"] !== "1" && 
            $data["passwordnotice"] !== "2") {
            $err_msg = "パスワード通知の設定が不正です。(" . $data['passwordnotice']. ")";
            return FALSE;
         }
    }

    /* subjectsw機能が有効になっている場合 */
    if (is_active_plugin("pluginsubjectswactive")) {

        /* メール本文に定型文を挿入する設定 */
        if (($data["useaddmessageheader"] != "yes") && ($data["useaddmessageheader"] != "no")) {
            $err_msg = "メール本文に定型文を挿入する設定が不正です。(" . $data["useaddmessageheader"] . ")";
            return FALSE;
        }

        /* 本文追記のファイルパス */
        $ret = check_messagetmplpath($data["messagetmpljppath"], 
                                     $data["messagetmplenpath"],
                                     $data["messagetmplbothpath"], $errormsg);
        if (!$ret) {
            $err_msg = $errormsg;
            return FALSE;
        }

        /* 件名判定暗号化の設定 */
        if (($data["useencryptsubject"] != "yes") && ($data["useencryptsubject"] != "no")) {
            $err_msg = "件名判定安全化の設定が不正です。(". $data["useencryptsubject"]. ")";
            return FALSE;
        }

        /* 件名判定文字列(日本語) */
        $ret = check_str($data["subjectencryptstringjp"], "[]{}()@!#$%&+*=-/:;.,?_", 0, 256);
        if (!$ret) {
            $err_msg = "件名判定文字列(日本語)の形式が不正です。(". $data["subjectencryptstringjp"]. ")";
            return FALSE;
        } 

        /* 件名判定文字列(英語) */
        $ret = check_str($data["subjectencryptstringen"], "[]{}()@!#$%&+*=-/:;.,?_", 0, 256);
        if (!$ret) {
            $err_msg = "件名判定文字列(英語)の形式が不正です。(". $data["subjectencryptstringen"]. ")";
            return FALSE;
        }
    }

    /* 安全化した添付ファイルのContent-Type */
    if ($data["zipattachmentcontenttype"] !== "") {
        /* 形式チェック */
        if (check_content_type($data["zipattachmentcontenttype"]) !== TRUE) {
            $err_msg = "安全化した添付ファイルのContent-Type" . $err_msg;
            return FALSE;
        }
    }

    return TRUE;
}

/*********************************************************
 * check_messagetmplpath
 *
 * 本文追記のファイルパスの入力値をチェックする
 * ファイルを存在しない場合、空ファイルを作成する
 * 
 * [引数]
 *       $jp_path       本文追記のファイルパス(日本語)
 *       $en_path       本文追記のファイルパス(英語)
 *       $both_path     本文追記のファイルパス(両方)
 *       &$errormsg     エラーメッセージ(参照データ)
 *
 * [返り値]
 *      TRUE            正常
 *      FALSE           異常
 **********************************************************/
function check_messagetmplpath($jp_path, $en_path, $both_path, &$errormsg)
{
    if (strlen($jp_path) > 256) {
        $errormsg = "本文追記のファイルパス(日本語)が長すぎです。($jp_path)";
        return FALSE;
    }

    if (strlen($en_path) > 256) {
        $errormsg = "本文追記のファイルパス(英語)が長すぎです。($en_path)";
        return FALSE;
    }

    if (strlen($both_path) > 256) {
        $errormsg = "本文追記のファイルパス(両方)が長すぎです。($both_path)";
        return FALSE;
    }

    if ($jp_path !== "") {
        /* ファイルの存在をチェック */
        if (!file_exists($jp_path)) {
            /* ファイルを作成 */
            if (touch($jp_path) === FALSE) { 
                $errormsg = "本文追記のファイル(日本語)の作成に失敗しました。($jp_path)";
                return FALSE;
            }
        }
       /* 通常ファイルかどうかを調べる */
       if(!is_file($jp_path)) {
            $errormsg = "本文追記のファイル(日本語)は通常ファイルではありません。($jp_path)";
            return FALSE;
       }
    }

    if ($en_path !== "") {
        /* ファイルの存在をチェック */
        if (!file_exists($en_path)) {
            /* ファイルを作成 */
            if (!touch($en_path)) {
                $errormsg = "本文追記のファイル(英語)の作成に失敗しました。($en_path)";
                return FALSE;
            }
        }
       /* 通常ファイルかどうかを調べる */
       if(!is_file($en_path)) {
            $errormsg = "本文追記のファイル(日本語)は通常ファイルではありません。($en_path)";
            return FALSE;
       }
    }

    if ($both_path) {
        /* ファイルの存在をチェック */
        if (!file_exists($both_path)) {
            /* ファイルを作成 */
            if (!touch($both_path)) {
                $errormsg = "本文追記のファイル(両方)の作成に失敗しました。($both_path)";
                return FALSE;
            }
        }
        /* 通常ファイルかどうかを調べる */
        if(!is_file($both_path)) {
            $errormsg = "本文追記のファイル(日本語)は通常ファイルではありません。($both_path)";
            return FALSE;
        }
    }

    return TRUE;
}

/*********************************************************
 * check_db
 *
 * データベースファイルチェック
 *
 * [引数]
 *       $db_file	DBファイル
 *	 $db_type	DB形式
 *
 * [返り値]
 *	TRUE		正常
 *	FALSE		異常
 **********************************************************/
function check_db($db_file, $db_type)
{
    global $err_msg;

    /* 形式チェック　*/
    $ret = check_file($db_file);
    if ($ret === FAIL) {
        $err_msg = "DB" . $err_msg;
        return FALSE;
    /* DBファイルがない場合は作成 */
    } elseif ($ret === FAIL_NO_EXIST) {
        /* 空DB作成 */
        $type = 0;
        if ($db_type == "btree") {
            $type = 1;
        }
        if (make_db($db_file, $type) === FALSE) {
            $err_msg = "DB用の" . $err_msg;
            return FALSE;
        }
    /* チェックOKな場合は形式チェック */
    } else {
        $command = sprintf(CONFIRM_DB, $db_type, escapeshellcmd($db_file));
        $ret = system($command, $result);

        if ($result != 0) {
            $err_msg = "DB形式が不正です。";
            return FALSE;
        }
        if ($ret === FALSE){
            $err_msg = "DB形式の確認に失敗しました。";
            return FALSE;
        }
    }
    return TRUE;

}

/*********************************************************
 * check_tmpl
 *
 * テンプレートファイルチェック
 *
 * [引数]
 *       $filename	テンプレートファイル
 *       $df_tmpl	デフォルトテンプレートファイル
 *
 * [返り値]
 *	TRUE		正常
 *	FALSE		異常
 **********************************************************/
function check_tmpl($filename, $df_tmpl)
{
    global $err_msg;

    /* 形式チェック　*/
    $ret = check_file($filename);
    if ($ret === FAIL) {
        $err_msg = "テンプレート" . $err_msg;
        return FALSE;
    /* ファイルがない場合は作成 */
    } elseif ($ret === FAIL_NO_EXIST) {
        /* ディレクトリ書き込み権チェック */
        if (is_writable(dirname($filename)) !== TRUE) {
            $err_msg = "ディレクトリに書込み権がありません。(" . $filename . ")";
            return FALSE;
        }

        /* テンプレートファイル作成 */
        $ret = make_def_tmpl($filename, $df_tmpl);
        if ($ret === FALSE) {
            return FALSE;
        }
    }

    return TRUE;

}

/*********************************************************
 * set_disp_data
 *
 * 値を表示用配列にセットします
 *
 * [引数]
 *       $data		データ
 *	 $disp_data	データ
 *
 * [返り値]
 *	なし
 **********************************************************/
function set_disp_data($data, &$disp_data)
{
    /* commandpass */
    if (isset($data["commandpass"]) === TRUE) {
        $disp_data["commandpass"] = $data["commandpass"];
    }

    /* syslogfacility */
    if (isset($data["syslogfacility"]) === TRUE) {
        $disp_data["syslogfacility"] = $data["syslogfacility"];
    }

    /* encryptiontmpdir */
    if (isset($data["encryptiontmpdir"]) === TRUE) {
        $disp_data["encryptiontmpdir"] = $data["encryptiontmpdir"];
    }

    /* zipcommand */
    if (isset($data["zipcommand"]) === TRUE) {
        $disp_data["zipcommand"] = $data["zipcommand"];
    }

    /* zipcommandopt */
    if (isset($data["zipcommandopt"]) === TRUE) {
        $disp_data["zipcommandopt"] = $data["zipcommandopt"];
    }

    /* senderdb */
    if (isset($data["sd_dbfile"]) === TRUE) {
        $disp_data["sd_dbfile"] = $data["sd_dbfile"];
        $disp_data["sd_dbtype"] = $data["sd_dbtype"];
        $disp_data["senderdb"] = $data["sd_dbtype"] . ":" . $data["sd_dbfile"];
    }

    /* rcptdb */
    if (isset($data["rp_dbfile"]) === TRUE) {
        $disp_data["rp_dbfile"] = $data["rp_dbfile"];
        $disp_data["rp_dbtype"] = $data["rp_dbtype"];
        $disp_data["rcptdb"] = $data["rp_dbtype"] . ":" . $data["rp_dbfile"];
    }

    /* extensiondb */
    if (isset($data["ex_dbfile"]) === TRUE) {
        $disp_data["ex_dbfile"] = $data["ex_dbfile"];
        $disp_data["ex_dbtype"] = $data["ex_dbtype"];
        $disp_data["extensiondb"] = $data["ex_dbtype"] . ":" . $data["ex_dbfile"];
    }

    /* commanddb */
    if (isset($data["com_dbfile"]) === TRUE) {
        $disp_data["com_dbfile"] = $data["com_dbfile"];
        $disp_data["com_dbtype"] = $data["com_dbtype"];
        $disp_data["commanddb"] = $data["com_dbtype"] . ":" . $data["com_dbfile"];
    }

    /* templatepath */
    if (isset($data["templatepath"]) === TRUE) {
        $disp_data["templatepath"] = $data["templatepath"];
    }

    /* rcpttemplatepath */
    if (isset($data["rcpttemplatepath"]) === TRUE) {
        $disp_data["rcpttemplatepath"] = $data["rcpttemplatepath"];
    }

    /* sendmailcommand */
    if (isset($data["sendmailcommand"]) === TRUE) {
        $disp_data["sendmailcommand"] = $data["sendmailcommand"];
    }

    /* zipfilename */
    if (isset($data["zipfilename"]) === TRUE) {
        $disp_data["zipfilename"] = $data["zipfilename"];
    }

    /* mailsavetmpdir */
    if (isset($data["mailsavetmpdir"]) === TRUE) {
        $disp_data["mailsavetmpdir"] = $data["mailsavetmpdir"];
    }

    /* passwordlength */
    if (isset($data["passwordlength"]) === TRUE) {
        $disp_data["passwordlength"] = $data["passwordlength"];
    }

    /* strcode */
    if (isset($data["strcode"]) === TRUE) {
        $disp_data["strcode"] = $data["strcode"];
    }

    /* defaultencryption */
    if (isset($data["defaultencryption"]) === TRUE) {
        $disp_data["defaultencryption"] = $data["defaultencryption"];
    }

    /* defaultpassword */
    if (isset($data["defaultpassword"]) === TRUE) {
        $disp_data["defaultpassword"] = $data["defaultpassword"];
    }

    /* userpolicy */
    if (isset($data["userpolicy"]) === TRUE) {
        $disp_data["userpolicy"] = $data["userpolicy"];
    }

    /* userpolicyが"yes"の時だけLDAPデータ処理 */
    if (isset($data["userpolicy"]) === TRUE && $data["userpolicy"] == "yes") {

        /* ldapserver */
        if (isset($data["ldapserver"]) === TRUE) {
            $disp_data["ldapserver"] = $data["ldapserver"];
        }

        /* ldapport */
        if (isset($data["ldapport"]) === TRUE) {
            $disp_data["ldapport"] = $data["ldapport"];
        }

        /* server & port */
        if (isset($data["ldapserver"]) === TRUE && 
            isset($data["ldapport"]) === TRUE) { 
            $disp_data["ldapuri"] = "ldap://" . $data["ldapserver"] . ":" . $data["ldapport"] . "/";
        }

        /* ldapbasedn */
        if (isset($data["ldapbasedn"]) === TRUE) {
            $disp_data["ldapbasedn"] = $data["ldapbasedn"];
        }

        /* ldapbinddn */
        if (isset($data["ldapbinddn"]) === TRUE) {
            $disp_data["ldapbinddn"] = $data["ldapbinddn"];
        }

        /* ldapbindpassword */
        if (isset($data["ldapbindpassword"]) === TRUE) {
            $disp_data["ldapbindpassword"] = $data["ldapbindpassword"];
        }

        /* ldapfilter */
        if (isset($data["ldapfilter"]) === TRUE) {
            $disp_data["ldapfilter"] = $data["ldapfilter"];
        }
    }

    /* 宛先にパスワードを通知する機能が有効になっている場合 */
    if (is_active_plugin("pluginpassnoticeactive")) {
        /* passwordnotice */
        if (isset($data["passwordnotice"])) {
             $disp_data["passwordnotice"] = $data["passwordnotice"];
        }
    }

    /* subjectsw機能が有効になっている場合 */
    if (is_active_plugin("pluginsubjectswactive")) {
        /* useaddmessageheader */
        if (isset($data["useaddmessageheader"])) {
            $disp_data["useaddmessageheader"] = $data["useaddmessageheader"];
        }

        /* messagetmpljppath */
        if (isset($data["messagetmpljppath"])) {
            $disp_data["messagetmpljppath"] = $data["messagetmpljppath"];
        }

        /* messagetmplenpath */
        if (isset($data["messagetmplenpath"])) {
            $disp_data["messagetmplenpath"] = $data["messagetmplenpath"];
        }

        /* messagetmplbothpath */
        if (isset($data["messagetmplbothpath"])) {
            $disp_data["messagetmplbothpath"] = $data["messagetmplbothpath"];
        }
  
        /* useencryptsubject */
        if (isset($data["useencryptsubject"])) {
            $disp_data["useencryptsubject"] = $data["useencryptsubject"];
        }

        /* subjectencryptstringjp */
        if (isset($data["subjectencryptstringjp"])) {
            $disp_data["subjectencryptstringjp"] = $data["subjectencryptstringjp"];
        }

        /* subjectencryptstringen */
        if (isset($data["subjectencryptstringen"])) {
            $disp_data["subjectencryptstringen"] = $data["subjectencryptstringen"];
        }
    }

    /* 安全化した添付ファイルのContent-Type */
    if (isset($data["zipattachmentcontenttype"]) === TRUE) {
        $disp_data["zipattachmentcontenttype"] = $data["zipattachmentcontenttype"];
    }

    return;

}
/*********************************************************
 * make_def_tmpl
 *
 * テンプレートファイル作成
 *
 * [引数]
 *       $filename      ファイル名
 *       $def_tmpl       デフォルトテンプレートファイル
 *
 * [返り値]
 *      TRUE            成功
 *      FALSE           失敗
 **********************************************************/
function make_def_tmpl($filename, $def_tmpl)
{
    global $err_msg;
    global $basedir;

    /* デフォルトのテンプレートファイルパス作成 */
    $def_tmpl = $basedir .  ETCDIR . $def_tmpl;

    /* デフォルトのテンプレートファイルチェック */
    if (is_readable_file($def_tmpl) === FALSE) {
        return FALSE;
    }

    /* ファイルコピー */
    if (copy($def_tmpl, $filename) === FALSE) {
        $err_msg = "テンプレートファイルの作成に失敗しました。";
        return FALSE;
    }

    return TRUE;

}

/*********************************************************
 * is_active_plugin
 *
 * プログラムは有効しているかチェックする
 *
 * [引数]
 *       $item          設定項目
 *
 * [返り値]
 *      TRUE            有効
 *      FALSE           無効
 **********************************************************/
function is_active_plugin($item)
{
    global $web_conf;

    if (isset($web_conf["postldapadmin"][$item])) {
        if ($web_conf["postldapadmin"][$item] === "yes") {
            return TRUE;
        }
    }

    return FALSE;
}

/***********************************************************
 * 初期処理
 **********************************************************/
/* 値の初期化 */
$tag = array();

/* セッションキーを変数に代入 */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* 設定ファイルタブ管理ファイル読込、セッションのチェック */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* SaMMA設定ファイル読込み */
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaconf"]);
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/
/* 古いcommandpass退避 */
if (isset($samma_conf["commandpass"]) === TRUE) {
    $commandpass = $samma_conf["commandpass"];
}

/* 処理の分岐 */

/* 更新 */
if (isset($_POST["mod"]) === TRUE) {
    /* 値保持用データ取得 */
    set_disp_data($_POST, $samma_conf);
    /* 入力チェック */
    if (check_conf_data($samma_conf) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* 更新 */
        if (mod_samma_conf($samma_conf) === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
        /* 成功 */
        } else {
            $err_msg = "設定ファイルを更新しました。";
            result_log(OPERATION . ":OK:" . $err_msg);
           /* メニュー画面へ遷移 */
           dgp_location("../index.php", $err_msg);
           exit (0);
        }
    }

/* キャンセル */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* メニュー画面へ遷移 */
    dgp_location("../index.php", $err_msg);
    exit (0);

}

/***********************************************************
 * 表示処理
 **********************************************************/

/* 共通のタグ設定 */
set_tag_common($tag);

/* 結果を取得しタグと置き換え */
display_result($tag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
