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
 * $RCSfile: add.php,v $
 * $Revision: 1.6 $
 * $Date: 2013/08/30 06:18:02 $
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

define("OPERATION", "受信者設定追加");
define("TMPLFILE", "samma/samma_admin_rcpt_add.tmpl");

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
    global $add_data;
    global $samma_conf;

    /* ドメイン */
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

    $extension = "";
    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* 拡張子 */
        if (isset($add_data["extension"]) === TRUE) {
            $extension = escape_html($add_data["extension"]);
        }
    }

    $tag["<<DOMAIN>>"] =  $domain;
    $tag["<<PASS_RADIO_R>>"] = $pass_radio_r;
    $tag["<<PASS_RADIO_I>>"] = $pass_radio_i;
    $tag["<<INDIVIPASS>>"] = $indivipass;
    $tag["<<RULE_RADIO_ON>>"] = $rule_radio_on;
    $tag["<<RULE_RADIO_OFF>>"] = $rule_radio_off;
    $tag["<<EXTENSION>>"] = $extension;

    return TRUE;

}

/*********************************************************
 * add_rcpt_dbdata
 *
 * データの追加
 *
 * [引数]
 *	$add_data	追加データ
 *
 * [返り値]
 *	SUCCESS		正常
 *	FAIL		異常
 *	FALSE_EXIST	異常(既にデータあり)
 **********************************************************/
function add_rcpt_dbdata($add_data)
{
    global $db_file;
    global $db_type;
    global $ex_db_file;
    global $ex_db_type;
    global $samma_conf;

    /* 登録データ作成 */
    $key = $add_data["domain"];
    /* 重複チェック用データ作成 */
    if ($add_data["rule"] == 1) {
        $check_key = "!" . $key;
    /* 非対象 */
    } elseif ($add_data["rule"] == 0) {
        $check_key = $key;
        $key = "!" . $key;
    }

    /* 個別かつ対象ドメインはパスワード登録 */
    $value = "";
    if ($add_data["password"] == 0 && $add_data["rule"] == 1) {
        $value = $add_data["indivipass"];
    }

    /* DBへ追加 */
    $ret = db_add($db_file, $db_type, $key, $check_key, $value);
    if ($ret !== SUCCESS) {
        return $ret;
    }

    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* 拡張子DBへ追加 */
        $ret = extension_db_add($ex_db_file, $ex_db_type, $add_data["domain"], $add_data["extension"]);
        if ($ret !== SUCCESS) {
            return $ret;
        }
    }

    return SUCCESS;

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
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/
/* DBファイル決定 */
$db_file = "";
$db_type = "";
if (isset($samma_conf["rcptdb"]) === TRUE) {
    $files = explode(":", $samma_conf["rcptdb"], 2);
    $db_type = $files[0];
    $db_file = $files[1];
} else {
    $err_msg = "DBファイルが設定されていません。";
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* 拡張子DBファイル決定 */
$ex_db_file = "";
$ex_db_type = "";
if (isset($samma_conf["extensiondb"]) === TRUE) {
    $ex_files = explode(":", $samma_conf["extensiondb"], 2);
    $ex_db_type = $ex_files[0];
    $ex_db_file = $ex_files[1];
    $tag["<<EXTENSION_START>>"] = "";
    $tag["<<EXTENSION_END>>"] = "";
} else {
    $tag["<<EXTENSION_START>>"] = "<!--";
    $tag["<<EXTENSION_END>>"] = "-->";
}

/* 保持用値取得 */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

/* 処理の分岐 */
/* 追加 */
if (isset($_POST["add"]) === TRUE) {
    /* 値取得 */
    $add_data["domain"] = strtolower($_POST["adddomain"]);
    $add_data["password"] = $_POST["password"];
    $add_data["rule"] = $_POST["rule"];
    $add_data["indivipass"] = $_POST["indivipass"];
    if (isset($samma_conf["extensiondb"]) === TRUE) {
        $add_data["extension"] = $_POST["addextension"];
    }

    /* 入力チェック */
    if (check_rcptadd_data($add_data) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* DB登録 */
        /* 登録失敗はシステムエラー */
        $ret = add_rcpt_dbdata($add_data);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        /* 既にいるデータ登録は通常エラー */
        } elseif ($ret === FAIL_EXIST) {
            $err_msg = "受信者設定は" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
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
