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
 * ユーザ用SaMMA受信者一覧画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.9 $
 * $Date: 2013/08/22 09:32:08 $
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

define("TMPLFILE", "samma/samma_user_menu.tmpl");
define("OPERATION", "安全化設定一覧");

/***********************************************************
 * 関数
 **********************************************************/
/*********************************************************
 * print_result
 *
 * 検索結果表示処理
 *
 * [引数]
 *      $tag  置き換えタグの配列 
 *
 * [返り値]
 *       なし
 **********************************************************/
function print_result(&$looptag)
{
    global $rp_data;
    global $del_list;

    /* ドメイン数 */
    $domain_count = 0;

    /* データなしは空表示 */
    if (is_array($rp_data) === FALSE) {
        $looptag = array();
        return;
    }

    /* ソート */
    uksort($rp_data, DOMAIN_SORT);

    /* 表示 */
    foreach ($rp_data as $key => $value) {
        /* 初期代入 */
        $rule = DISP_EFFECT;
        $passwd = DISP_RANDOM;
        $domain = $key;
        $rawpw = "";

        /* パスワード */
        if ($value != "") {
            $passwd = DISP_INDIVI;
            $rawpw = $value;
        }

        /* 暗号化ルール決定 */
        $str = explode("!", $key, 2);
        if ($str[0] != $key) {
            $domain = $str[1];
            $rule = DISP_INEFFECT;
            $passwd = DISP_NOPASS;
        }

        /* エスケープ */
        $domain = escape_html($domain);
        $rawpw = escape_html($rawpw);
        $cnv_key = str_replace("'", "\'", $key);

        $looptag[$domain_count]["<<KEY>>"] = $key;
        $looptag[$domain_count]["<<DOMAIN>>"] = $domain;
        $looptag[$domain_count]["<<PASSWD>>"] = $passwd;
        $looptag[$domain_count]["<<RAWPD>>"] = $rawpw;
        $looptag[$domain_count]["<<RULE>>"] = $rule;
        $looptag[$domain_count]["<<MOD_BTN>>"] = <<<EOD
<input type="button" class="list_mod_btn" onClick="allSubmit('samma_mod.php', '$cnv_key')" title="編集">

EOD;
        $domain_count++;
    }
    return TRUE;

}

/***********************************************************
 * 初期処理
 **********************************************************/
$tag = array();
$looptag = array();

/* セッションキーを変数に代入 */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = user_init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* メッセージ取得 */
if (isset($_POST["msg"]) === TRUE) {
    $err_msg = escape_html($_POST["msg"]);
}

/***********************************************************
 * main処理
 **********************************************************/
/* ユーザ名格納 */
$user = $env['loginuser'];
$userdn = $env['user_selfdn'];

/* ユーザ情報の取得 */
$ret = get_userdata($userdn);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    $err_msg = "ユーザ情報の取得に失敗しました。";
    syserr_display();
    exit (1);
}

$dispusr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = escape_html($ldapdata[0][$dispusr][0]);

/* 保持用値取得 */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

/* 処理の分岐 */
/* 新規追加 */
if (isset($_POST["new_add"]) === TRUE) {
    /* 追加画面へ遷移 */
    dgp_location("samma_add.php");
    exit (0);

/* チェックしたものを削除 */
} elseif (isset($_POST["check_del"]) === TRUE) {
    /* チェックなし */
    if (isset($_POST["delete"]) === FALSE) {
        $err_msg = "削除対象が選択されていません。";
    } else {
        /* ユーザでのバインドを無効化 */
        $env['user_self'] = FALSE;

        /* ドメイン/メールアドレスの削除 */
        $ret = ldap_enc_del($userdn, $_POST["delete"]);
        /* 削除対象がいないエラーは通常エラー */
        if ($ret === LDAP_ERR_NODATA) {
            $err_msg = "受信者設定の" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            /* 成功が合った場合 */
            if ($suc_msg != "") {
                $suc_msg = "受信者設定の" . $suc_msg;
                result_log(OPERATION . ":OK:" . $suc_msg);
                $err_msg .= "<br>" . $suc_msg;
            }
        /* エラーはシステムエラー */
        } elseif ($ret !== LDAP_OK) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* ユーザ用メッセージに変更 */
            $delete = implode(", ", $_POST["delete"]);
            $err_msg = "受信者設定の削除に失敗しました。(" . $delete . ")";
            syserr_display();
            exit (1);
        /* 正常 */
        } else {
            $err_msg = "受信者設定の" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);
        }

    }
}

/* 受信者設定取得 */
$rp_data = array();
/* ユーザでのバインドを無効化 */
$env['user_self'] = FALSE;
$ret = get_user_data($userdn, $rp_data);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    $err_msg = "安全化設定情報の取得に失敗しました。";
    syserr_display();
    exit (1);
}

/***********************************************************
 * 表示処理
 **********************************************************/
/* 共通のタグ設定 */
set_tag_common($tag);

/* 結果を取得しタグと置き換え */
print_result($looptag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, $looptag, STARTTAG, ENDTAG);
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
