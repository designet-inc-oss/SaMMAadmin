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
 * SaMMA受信者一覧画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.8 $
 * $Date: 2013/08/30 06:17:18 $
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

define("OPERATION", "受信者設定一覧");
define("TMPLFILE", "samma/samma_admin_rcpt_menu.tmpl");

/*********************************************************
 * print_result
 *
 * 検索結果表示処理
 *
 * [引数]
 *       なし
 *
 * [返り値]
 *       なし
 **********************************************************/
function print_result(&$looptag)
{
    global $rp_data;
    global $ex_data;
    global $del_list;
    global $samma_conf;

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
        $extension = "";

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

        /* 受信者DBにあって拡張子DBにもある場合 */
        if (isset($samma_conf["extensiondb"]) === TRUE) {
            if (isset($ex_data["$domain"]) === TRUE) {

                # ここで拡張子のvalueを設定する
                $extension = $ex_data["$domain"];
            }
        }

        /* エスケープ */
        $domain = escape_html($domain);
        $extension = escape_html($extension);
        $cnv_key = str_replace("'", "\'", $key);

        $looptag[$domain_count]["<<KEY>>"] = $key;
        $looptag[$domain_count]["<<DOMAIN>>"] = $domain;
        $looptag[$domain_count]["<<PASSWD>>"] = $passwd;
        $looptag[$domain_count]["<<RULE>>"] = $rule;
        $looptag[$domain_count]["<<EXTENSION>>"] = $extension;
        $looptag[$domain_count]["<<CNV_KEY>>"] = $cnv_key;

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

/* メッセージ取得 */
if (isset($_POST["msg"]) === TRUE) {
    $err_msg = escape_html($_POST["msg"]);
}

/***********************************************************
 * main処理
 **********************************************************/
/* DBファイル決定 */
$db_file = "";
if (isset($samma_conf["rcptdb"]) === TRUE) {
    $files = explode(":", $samma_conf["rcptdb"], 2);
    $db_file = $files[1];
} else {
    $err_msg = "DBファイルが設定されていません。";
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}
    
/* 拡張子DBファイル決定 */
$ex_db_file = "";
if (isset($samma_conf["extensiondb"]) === TRUE) {
    $ex_files = explode(":", $samma_conf["extensiondb"], 2);
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
/* 新規追加 */
if (isset($_POST["new_add"]) === TRUE) {
    /* 追加画面へ遷移 */
    dgp_location("add.php");
    exit (0);

/* チェックしたものを削除 */
} elseif (isset($_POST["check_del"]) === TRUE) {
    /* チェックなし */
    if (isset($_POST["delete"]) === FALSE) {
        $err_msg = "削除対象が選択されていません。";
    } else {
        /* ドメイン/メールアドレスの削除 */
        $ret = db_del($db_file, $_POST["delete"]);
        /* 削除失敗(システムエラー) */
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        /* 削除失敗 */
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "受信者設定の" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            /* 成功があれば出力 */
            if ($suc_msg != "") {
                $suc_msg = "受信者設定の" . $suc_msg;
                result_log(OPERATION . ":OK:" . $suc_msg);
                $err_msg .= "<br>" . $suc_msg;
            }
        /* 成功 */
        } else {
            $err_msg = "受信者設定の" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);
        }
        $db_err_msg = $err_msg;

        /* 拡張子の削除 */
        if (isset($samma_conf["extensiondb"]) === TRUE) {
            $ret = extension_db_del($ex_db_file, $_POST["delete"]);
            /* 削除失敗(システムエラー) */
            if ($ret === FAIL) {
                result_log(OPERATION . ":NG:" . $err_msg);
                syserr_display();
                exit (1);
            /* 削除失敗 */
            } elseif ($ret === FAIL_DEL) {
                $err_msg = "拡張子設定の" . $err_msg;
                result_log(OPERATION . ":NG:" . $err_msg);
                $err_msg = $db_err_msg . "<br>" . $err_msg;
                /* 成功があれば出力 */
                if ($suc_msg != "") {
                    $suc_msg = "拡張子設定の" . $suc_msg;
                    result_log(OPERATION . ":OK:" . $suc_msg);
                    $err_msg .= "<br>" . $suc_msg;
                }
            } elseif ($ret === NO_CHANGE) {
                /* なにも出力しない */
            /* 成功 */
            } else {
                $err_msg = "拡張子設定の" . $suc_msg;
                result_log(OPERATION . ":OK:" . $err_msg);
                $err_msg = $db_err_msg . "<br>" . $err_msg;
            }
        }
    }
}

/* 受信者設定取得 */
$ret = db_search($db_file, $rp_data);
if ($ret === FAIL) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* 拡張子設定取得 */
if (isset($samma_conf["extensiondb"]) === TRUE) {
    $ret = db_search($ex_db_file, $ex_data);
    if ($ret === FAIL) {
        result_log(OPERATION . ":NG:" . $err_msg);
        syserr_display();
        exit (1);
    }
}

/***********************************************************
 * 表示処理
 **********************************************************/
/* 共通のタグ設定 */
set_tag_common($tag);

print_result($looptag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, $looptag, STARTTAG, ENDTAG);
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
