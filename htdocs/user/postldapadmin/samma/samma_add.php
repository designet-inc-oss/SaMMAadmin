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
 * $Revision: 1.4 $
 * $Date: 2013/08/22 02:02:18 $
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

define("OPERATION", "安全化設定追加");
define("TMPLFILE", "samma/samma_user_add.tmpl");

/***********************************************************
 * 関数
 **********************************************************/
/***********************************************************
 * set_tag_data()
 *
 * 置き換えタグの情報をセットする
 *
 * [引数]
 *      $tag  置き換えタグの配列
 *
 * [返り値]
 *       なし
 **********************************************************/
function set_tag_data(&$tag) {
    global $sesskey;
    global $dispusr;
    global $del_list;
    global $add_data;

    /* ユーザ名 */
    $user = escape_html($dispusr);
    $tag["<<USER>>"] = $user;

    /* ドメイン名 */
    $domain = "";
    if (isset($add_data["domain"]) === TRUE) {
        $domain = escape_html($add_data["domain"]);
    }
    $tag["<<DOMAIN>>"] = $domain;

    /* パスワード */
    $pass_radio_r = "checked";
    $pass_radio_i = "";

    if (isset($add_data["password"]) === TRUE) {
        if ($add_data["password"] == 0) {
            $pass_radio_r = "";
            $pass_radio_i = "checked";
        }
    }
    $tag["<<PASS_RADIO_R>>"] = $pass_radio_r;
    $tag["<<PASS_RADIO_I>>"] = $pass_radio_i;

    /* 個別パスワード */
    $indivipass = "";
    if (isset($add_data["indivipass"]) === TRUE) {
        $indivipass = escape_html($add_data["indivipass"]);
    }
    $tag["<<INDIVIPASS>>"] = $indivipass;

    /* 暗号化ルール */
    $rule_radio_on = "checked";
    $rule_radio_off = "";

    if (isset($add_data["rule"]) === TRUE) {
        if ($add_data["rule"] == 0) {
            $rule_radio_on = "";
            $rule_radio_off = "checked";
        }
    }
    $tag["<<RULE_RADIO_ON>>"] = $rule_radio_on;
    $tag["<<RULE_RADIO_OFF>>"] = $rule_radio_off;

    return TRUE;
}


/***********************************************************
 * 初期処理
 **********************************************************/
$tag = array();

/* セッションキーを変数に代入 */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* 設定ファイル、タブ管理ファイル読込、セッションチェック */
$ret = user_init();
if ($ret === FALSE) {
    $sys_err = TRUE;
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/
/* ユーザ名格納 */
$user = $env['loginuser'];
$userdn = $env['user_selfdn'];

/* ユーザ情報の取得 */
$ret = get_userdata ($userdn);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    $err_msg = "ユーザ情報の取得に失敗しました。";
    $sys_err = TRUE;
    $pg->display(NULL);
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
        /* ユーザでのバインド無効 */
        $env['user_self'] = FALSE;

        /* LDAP登録 */
        $ret = add_rcpt_data($add_data);
        /* 既にいるデータ登録は通常エラー */
        if ($ret === FAIL_EXIST) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* エラーメッセージ変更 */
            $err_msg = "受信者設定の追加に失敗しました。(" .  $add_data["domain"] . ")";
        /* 登録失敗はシステムエラー */
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* エラーメッセージ変更 */
            $err_msg = "受信者設定の追加に失敗しました。(" .  $add_data["domain"] . ")";
            $sys_err = TRUE;
            syserr_display();
            exit (1);
        /* 正常 */
        } else {
            $err_msg = "受信者設定を追加しました。(" . $add_data["domain"] . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* 一覧画面へ */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
    }
/* キャンセル */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* 追加画面へ遷移 */
    dgp_location("index.php");
    exit (0);

}


/***********************************************************
 * 表示処理
 **********************************************************/
/* 共通のタグ設定 */
set_tag_common($tag);

/* bodyの表示処理 */
set_tag_data($tag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
