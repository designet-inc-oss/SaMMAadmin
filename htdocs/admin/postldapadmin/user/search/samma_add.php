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
 * SaMMA受信者追加画面
 *
 * $RCSfile: samma_add.php,v $
 * $Revision: 1.5 $
 * $Date: 2013/08/30 06:05:57 $
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

define("OPERATION", "ユーザの受信者設定追加");
define("TMPLFILE", "samma/samma_user_rcpt_add.tmpl");

/*********************************************************
 * display_result
 *
 * hiddenで渡すデータの置き換え
 *
 * [引数]
 *       $tag　置き換えタグ
 *
 * [返り値]
 *       なし
 **********************************************************/
function display_result(&$tag) {
    global $sesskey;
    global $dispusr;
    global $del_list;
    global $add_data;
    global $user;
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

    /* ユーザ名 */
    $user = escape_html($dispusr);

    /* ドメイン名 */
    $domain = "";
    if (isset($add_data["domain"]) === TRUE) {
        $domain = escape_html($add_data["domain"]);
    }

    /* パスワード */
    $pass_radio_r = "checked";
    $pass_radio_i = "";

    if (isset($add_data["password"]) === TRUE) {
        if ($add_data["password"] == 0) {
            $pass_radio_r = "";
            $pass_radio_i = "checked";
        }
    }

    /* 個別パスワード */
    $indivipass = "";
    if (isset($add_data["indivipass"]) === TRUE) {
        $indivipass = escape_html($add_data["indivipass"]);
    }

    /* 暗号化ルール */
    $rule_radio_on = "checked";
    $rule_radio_off = "";

    if (isset($add_data["rule"]) === TRUE) {
        if ($add_data["rule"] == 0) {
            $rule_radio_on = "";
            $rule_radio_off = "checked";
        }
    }

    /* 保持用値埋め込み */
    $hidden_del = "";
    if (is_array($del_list) === TRUE) {
        foreach ($del_list as $delval) {
            $hidden_del .= "<input type=\"hidden\" name=\"delete[]\" value=\"$delval\">";
        }
    }

    /* hiddenデータ作成 */
    $hidden_data = "";
    foreach($hiddendata as $hidkey => $hidval) {
        $hidval = escape_html($hidval);
        $hidden_data .= "<input type=\"hidden\" name=\"$hidkey\" value=\"$hidval\">";
    }

    $tag["<<USER>>"] = $user;
    $tag["<<DOMAIN>>"] = $domain;
    $tag["<<PASS_RADIO_R>>"] = $pass_radio_r;
    $tag["<<PASS_RADIO_I>>"] = $pass_radio_i;
    $tag["<<INDIVIPASS>>"] = $indivipass;
    $tag["<<RULE_RADIO_ON>>"] = $rule_radio_on;
    $tag["<<RULE_RADIO_OFF>>"] = $rule_radio_off;
    $tag["<<HIDDEN_DEL>>"] = $hidden_del;
    $tag["<<HIDDEN_DATA>>"] = $hidden_data;

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

$dispattr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = $ldapdata[0][$dispattr][0];

/* フォーム情報格納 */
$name_match = $_POST["name_match"];
/* フォームに入力された値の複合化 */
if (isset($_POST['form_name']) === TRUE) {
    $form_name = str_rot13($_POST['form_name']);
    $form_name = base64_decode($form_name);
}

$dispusr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = escape_html($ldapdata[0][$dispusr][0]);

/* 保持用値取得 */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

/* 処理の分岐 */
/* 追加 */
if (isset($_POST["add"]) === TRUE) {
    /* 値取得 */
    $add_data["domain"] = $_POST["adddomain"];
    $add_data["password"] = $_POST["password"];
    $add_data["rule"] = $_POST["rule"];
    $add_data["indivipass"] = $_POST["indivipass"];

    /* 入力チェック */
    if (check_rcptadd_data($add_data) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* LDAP登録 */
        $ret = add_rcpt_data($add_data);
        /* 既にいるデータ登録は通常エラー */
        if ($ret === FAIL_EXIST) {
            result_log(OPERATION . ":NG:" . $err_msg);
        /* 登録失敗はシステムエラー */
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        /* 正常 */
        } else {
            $err_msg = "受信者設定を追加しました。(" . $add_data["domain"] . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* 一覧画面へ */
            page_location_search("samma_list.php", $err_msg);
            exit (0);
        }
    }
/* キャンセル */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* 一覧画面へ遷移 */
    page_location_search("samma_list.php");
    exit (0);

}


/***********************************************************
 * 表示処理
 **********************************************************/

/* 共通のタグ設定 */
set_tag_common($tag);

display_result($tag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
