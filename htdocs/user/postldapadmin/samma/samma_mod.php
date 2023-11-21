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
 * SaMMA受信者変更画面
 *
 * $RCSfile: samma_mod.php,v $
 * $Revision: 1.9 $
 * $Date: 2013/08/22 09:32:35 $
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

define("OPERATION", "安全化設定変更");
define("TMPLFILE", "samma/samma_user_mod.tmpl");

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
    global $del_list;
    global $mod_data;
    global $dispusr;

    /* ユーザ名 */
    $user = escape_html($dispusr);
    $tag["<<USER>>"] = $user;

    /* ドメイン名 */
    $domain = "";
    if (isset($mod_data["domain"]) === TRUE) {
        $domain = escape_html($mod_data["domain"]);
    }
    $tag["<<DOMAIN>>"] = $domain;

    $disp_dom = "";
    if (isset($mod_data["disp_dom"]) === TRUE) {
        $disp_dom = escape_html($mod_data["disp_dom"]);
    }
    $tag["<<DISP_DOM>>"] = $disp_dom;

    /* パスワード */
    $pass_radio_r = "checked";
    $pass_radio_i = "";

    if (isset($mod_data["password"]) === TRUE) {
        if ($mod_data["password"] == 0) {
            $pass_radio_r = "";
            $pass_radio_i = "checked";
        }
    }
    $tag["<<PASS_RADIO_R>>"] = $pass_radio_r;
    $tag["<<PASS_RADIO_I>>"] = $pass_radio_i;

    /* 個別パスワード */
    $indivipass = "";
    if (isset($mod_data["indivipass"]) === TRUE) {
        $indivipass = escape_html($mod_data["indivipass"]);
    }
    $tag["<<INDIVIPASS>>"] = $indivipass;

    /* 暗号化ルール */
    $rule_radio_on = "checked";
    $rule_radio_off = "";

    if (isset($mod_data["rule"]) === TRUE) {
        if ($mod_data["rule"] == 0) {
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
    $pg->display(NULL);
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

/* 初期表示 */
$mod_data = array();
if (isset($_POST["domname"]) === TRUE) {
    $key_domain = $_POST["domname"];
}
if (get_one_data($key_domain, $mod_data) === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    $err_msg = "安全化設定情報の取得に失敗しました。";
    $sys_err = TRUE;
    syserr_display();
    exit (1);
}

/* 処理の分岐 */
/* 変更 */
if (isset($_POST["mod"]) === TRUE) {
    /* 現在のルール退避 */
    $old_rule = $mod_data["rule"];

    /* 値取得 */
    $mod_data["password"] = $_POST["password"];
    $mod_data["rule"] = $_POST["rule"];
    $mod_data["indivipass"] = $_POST["indivipass"];

    /* 入力チェック */
    if (check_rcptmod_data($mod_data) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* ユーザでのバインド無効 */
        $env['user_self'] = FALSE;

        /* 更新 */
        $ret = mod_rcpt_data($mod_data, $old_rule);

        /* システムエラー */
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* ユーザ用メッセージ変更 */
            $err_msg = "受信者設定の更新に失敗しました。(" . $mod_data["disp_dom"] . ")";
            $sys_err = TRUE;
            syserr_display();
            exit (1);
        /* 更新失敗 */
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* ユーザ用メッセージ変更 */
            $err_msg = "受信者設定の更新に失敗しました。(" . $mod_data["disp_dom"] . ")";
        } else {
            /* 成功 */
            $err_msg = "受信者設定の更新に成功しました。(" . $mod_data["disp_dom"] . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* 一覧画面へ */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
    }
/* 削除 */
} elseif (isset($_POST["del"]) === TRUE) {
    /* 削除対象作成 */
    $del_dom[] = $mod_data["domain"];

    /* ユーザでのバインド無効 */
    $env['user_self'] = FALSE;

    /* 削除 */
    $ret = ldap_enc_del($userdn, $del_dom);
    /* 削除対象がいないエラーは通常エラー */
    if ($ret === LDAP_ERR_NODATA) {
        $err_msg = "受信者設定の" . $err_msg;
        result_log(OPERATION . ":NG:" . $err_msg);
    /* エラーはシステムエラー */
    } elseif ($ret !== LDAP_OK) {
        result_log(OPERATION . ":NG:" . $err_msg);
        /* ユーザ用メッセージ変更 */
        $err_msg = "受信者設定の削除に失敗しました。(" . $mod_data["domain"] . ")";
        $sys_err = TRUE;
        syserr_display();
        exit (1);
    /* 正常 */
    } else {
        $err_msg = "受信者設定の" . $suc_msg;
        result_log(OPERATION . ":OK:" . $err_msg);

        /* 一覧画面へ */
        dgp_location("index.php", $err_msg);
        exit (0);
    }
/* キャンセル */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* 一覧画面へ遷移 */
    dgp_location("index.php");
    exit (0);

}


/***********************************************************
 * 表示処理
 **********************************************************/
/* 共通のタグ設定 */
set_tag_common($tag);

set_tag_data($tag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
