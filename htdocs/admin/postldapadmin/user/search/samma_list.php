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
 * $RCSfile: samma_list.php,v $
 * $Revision: 1.7 $
 * $Date: 2013/08/30 06:04:58 $
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

define("OPERATION", "ユーザの受信者設定一覧");
define("TMPLFILE", "samma/samma_user_rcpt_menu.tmpl");
define("STATUS_INPUT",  0);
define("STATUS_SEARCH", 1);

/*********************************************************
 * hidden_result
 *
 * hiddenで渡すデータの置き換え
 *
 * [引数]
 *       $tag　置き換えタグ
 *
 * [返り値]
 *       なし
 **********************************************************/
function hidden_result(&$tag) {
    global $sesskey;
    global $user;
    global $dispusr;
    global $del_list;
    global $mode;
    global $ldapdata;
    global $userdn;
    global $form_name;

    /* DNの暗号化 */
    $userdn = base64_encode($userdn);
    $userdn = str_rot13($userdn);

    /* 検索ユーザ名の暗号化 */
    $form_name = base64_encode($form_name);
    $form_name = str_rot13($form_name);

    /* hiddenで渡すデータを格納 */
    $hiddendata['dn'] = $userdn;
    $hiddendata['sk'] = $sesskey;
    $hiddendata['page'] = $_POST["page"];
    $hiddendata['filter'] = $_POST["filter"];
    $hiddendata['form_name'] = $form_name;
    $hiddendata['name_match'] = $_POST['name_match'];

    /* 保持用値埋め込み */
    $hidden_del = "";
    if (is_array($del_list) === TRUE) {
        foreach ($del_list as $delval) {
            $hidden_del .= "<input type=\"hidden\" name=\"delete[]\" value=\"$delval\">";
        }
    }

    $hidden = "";
    foreach($hiddendata as $hidkey => $hidval) {
        $hidval = escape_html($hidval);
        $hidden .= "<input type=\"hidden\" name=\"$hidkey\" value=\"$hidval\">";
    }

    $tag["<<HIDDEN>>"] = $hidden;
    $tag["<<HIDDEN_DEL>>"] = $hidden_del;

    return TRUE;

}

/*********************************************************
 * print_result
 *
 * 検索結果表示処理
 *
 * [引数]
 *       $looptag
 *
 * [返り値]
 *       なし
 **********************************************************/
function print_result(&$looptag)
{
    global $rp_data;
    global $del_list;

    $domain_count = 0;

    /* データなしは空表示 */
    if (is_array($rp_data) === FALSE) {
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

        /* パスワード */
        if ($value != "") {
            $passwd = DISP_INDIVI;
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
        $cnv_key = str_replace("'", "\'", $key);

        $looptag[$domain_count]["<<KEY>>"] = $key;
        $looptag[$domain_count]["<<DOMAIN>>"] = $domain;
        $looptag[$domain_count]["<<PASSWD>>"] = $passwd;
        $looptag[$domain_count]["<<RULE>>"] = $rule;
        $looptag[$domain_count]["<<MOD_BTN>>"] = "<input type=\"button\" class=\"list_mod_btn\" onClick=\"allSubmit('samma_mod.php', '$cnv_key')\" title=\"編集\">";

        $domain_count++;
    }
    return TRUE;

}
/***********************************************************
 * 初期処理
 **********************************************************/

/* 値の初期化 */
$tag = array();
$looptag = array();

/* セッションキーを変数に代入 */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* ユーザ情報格納 */
if (isset($_POST["dn"]) === TRUE) {
    $dn = $_POST["dn"];
    $userdn = str_rot13($dn);
    $userdn = base64_decode($userdn);
}
if (isset($_POST["page"]) === TRUE) {
    $page = $_POST["page"];
}
if (isset($_POST["filter"]) === TRUE) {
    $filter = $_POST["filter"];
}

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = init();
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
/* ページの形式チェック */
if (is_num_check($page) === FALSE) {
    $err_msg = "ページの値が不正です。";
    syserr_display();
    exit (1);
}

/* フィルタの複合化 */
if (sess_key_decode($filter, $dec_filter) === FALSE) {
    syserr_display();
    exit (1);
}

/* フィルタの形式チェック */
$fdata = explode(':', $dec_filter);
if (count($fdata) != 3) {
    $err_msg = "フィルタの形式が不正です。";
    syserr_display();
    exit (1);
}

/* DNの形式チェック */
$len = (-1) * strlen($web_conf[$url_data['script']]['ldapbasedn']);
$cmpdn = substr($userdn, $len);
if (strcmp($cmpdn, $web_conf[$url_data['script']]['ldapbasedn']) != 0) {
    $err_msg = "DNの形式が不正です。";
    syserr_display();
    exit (1);
}

/* ユーザ情報の取得 */
$ret = get_userdata($userdn);
if ($ret !== TRUE) {
    if ($ret !== LDAP_ERR_BIND) {
        $err_msg = "指定されたユーザはすでに削除されています。";
    }
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

$user = $ldapdata[0]["uid"][0];

$dispusr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = escape_html($ldapdata[0][$dispusr][0]);

/* フォーム情報格納 */
$form_name = $_POST["form_name"];
$name_match = $_POST["name_match"];

/* 保持用値取得 */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}
/* 処理の分岐 */
/* 新規追加 */
if (isset($_POST["new_add"]) === TRUE) {
    /* 追加画面へ遷移 */
    page_location("samma_add.php", $del_list);
    exit (0);

/* チェックしたものを削除 */
} elseif (isset($_POST["check_del"]) === TRUE) {
    /* チェックなし */
    if (isset($_POST["delete"]) === FALSE) {
        $err_msg = "削除対象が選択されていません。";
    } else {
        /* ドメイン/メールアドレスの削除 */
        $ret = ldap_enc_del($userdn, $_POST["delete"]);
        /* 削除対象がいないエラーは通常エラー */
        if ($ret === LDAP_ERR_NODATA) {
            $err_msg = "受信者設定の" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            /* 成功があった場合 */
            if ($suc_msg != "") {
                $suc_msg = "受信者設定の" . $suc_msg;
                result_log(OPERATION . ":OK:" . $suc_msg);
                $err_msg .= "<br>" . $suc_msg;
                $del_list = array();
            }
        /* エラーはシステムエラー */
        } elseif ($ret!== LDAP_OK) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display(CONTENT);
            exit (1);
        /* 正常 */
        } else {
            $err_msg = "受信者設定の" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);
            $del_list = array();
        }

    }

/* キャンセル */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* 検索画面に遷移 */
    dgp_location_search("index.php", $err_msg);
    exit (0);
}

/* 受信者設定取得 */
$rp_data = [];
$ret = get_user_data($userdn, $rp_data);
if ($ret === FALSE) {
    result_log(OPERATION . ":OK:" . $err_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * 表示処理
 **********************************************************/
/* 共通のタグ設定 */
set_tag_common($tag);

/* hiddenのタグ設定 */
hidden_result($tag);

/* 表示結果のタグ設定 */
print_result($looptag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, $looptag, STARTTAG, ENDTAG);
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
