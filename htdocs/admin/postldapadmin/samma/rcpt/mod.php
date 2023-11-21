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
 * $RCSfile: mod.php,v $
 * $Revision: 1.7 $
 * $Date: 2013/08/30 06:18:47 $
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

define("OPERATION", "受信者設定変更");
define("TMPLFILE", "samma/samma_admin_rcpt_mod.tmpl");

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
    global $samma_conf;

    /* ドメイン名 */
    $domain = "";
    if (isset($mod_data["domain"]) === TRUE) {
        $domain = escape_html($mod_data["domain"]);
    }

    $disp_dom = "";
    if (isset($mod_data["disp_dom"]) === TRUE) {
        $disp_dom = escape_html($mod_data["disp_dom"]);
    }

    /* パスワード */
    $pass_radio_r = "checked";
    $pass_radio_i = "";

    if (isset($mod_data["password"]) === TRUE) {
        if ($mod_data["password"] == 0) {
            $pass_radio_r = "";
            $pass_radio_i = "checked";
        }
    }

    /* 個別パスワード */
    $indivipass = "";
    if (isset($mod_data["indivipass"]) === TRUE) {
        $indivipass = escape_html($mod_data["indivipass"]);
    }

    /* 暗号化ルール */
    $rule_radio_on = "checked";
    $rule_radio_off = "";

    if (isset($mod_data["rule"]) === TRUE) {
        if ($mod_data["rule"] == 0) {
            $rule_radio_on = "";
            $rule_radio_off = "checked";
        }
    }

    $extension = "";
    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* 拡張子 */
        if (isset($mod_data["extension"]) === TRUE) {
            $extension = escape_html($mod_data["extension"]);
        }
    }

    $tag["<<DISP_DOM>>"] = $disp_dom;
    $tag["<<DOMAIN>>"] = $domain;
    $tag["<<PASS_RADIO_R>>"] = $pass_radio_r;
    $tag["<<PASS_RADIO_I>>"] = $pass_radio_i;
    $tag["<<INDIVIPASS>>"] = $indivipass;
    $tag["<<EXTENSION>>"] = $extension;
    $tag["<<RULE_RADIO_ON>>"] = $rule_radio_on;
    $tag["<<RULE_RADIO_OFF>>"] = $rule_radio_off;

    return TRUE;

}

/*********************************************************
 * mod_rcpt_dbdata
 *
 * データの修正
 *
 * [引数]
 *	$mod_data	変更データ
 *	$old_rule	変更前暗号化ルール
 *
 * [返り値]
 *	SUCCESS		正常
 *	FAIL		異常
 *	FAIL_NO_EXIST	異常(データなし)
 *	FAIL_EXIST	異常(データ既にあり)
 **********************************************************/
function mod_rcpt_dbdata($mod_data,  $old_rule)
{
    global $db_file;
    global $ex_db_file;
    global $samma_conf;

    /* 登録データ作成 */
    $old_key = $mod_data["domain"];

    /* 対象から非対象へ変更 */
    if ($old_rule == 1 && $mod_data["rule"] == 0) {
        $key = "!" . $old_key;
    /* 非対象から対象へ変更 */
    } elseif ($old_rule == 0 && $mod_data["rule"] == 1) {
        $str = explode("!", $mod_data["domain"], 2);
        $key = $str[1];
    /* 変更なし */
    } else {
        $key = $old_key;
    }

    /* パスワード */
    $value = "";
    if ($mod_data["password"] == 0) {
        $value = $mod_data["indivipass"];
    }

    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* 拡張子が空なら拡張子DBから削除 */
        if ($mod_data["extension"] == "") {
            $del_dom[] = $mod_data["domain"];
            $ret = extension_db_del($ex_db_file, $del_dom);
            if ($ret === NO_CHANGE) {
                /* なにもしない */
            } elseif ($ret === FAIL) {
                /* システムエラー */
                return $ret;
            } elseif ($ret !== SUCCESS) {
                /* 拡張子のエラー */
                return EXTENSION_ERR;
            }
        } else {
            /* 拡張子DB更新 */
            $ret = extension_db_mod($ex_db_file, $old_key, $mod_data["extension"]);
            if ($ret === NO_CHANGE) {
                /* なにもしない */
            } elseif ($ret === FAIL) {
                /* システムエラー */
                return $ret;
            } elseif ($ret !== SUCCESS) {
                /* 拡張子のエラー */
                return EXTENSION_ERR;
            }
        }
    }

    /* 暗号化ルール変更がない場合はそのまま変更 */
    if ($old_rule == $mod_data["rule"]) {

        /* DB更新 */
        $ret = db_mod($db_file, $key, $value);
        if ($ret !== SUCCESS) {
            return $ret;
        }
    } else {
        /* DB更新(キーも変更) */
        $ret = db_key_mod($db_file, $old_key, $key, $value);
        if ($ret !== SUCCESS) { 
            return $ret;
        }
    }

    return SUCCESS;

}
/*********************************************************
 * get_db_data
 *
 * データ取得
 *
 * [引数]
 *	$key		検索キー
 *     $db_file_path   データベースファイルパス
 *	$value		取得データ(参照渡し)
 *
 * [返り値]
 *	TRUE		正常
 *	FALSE		異常
 **********************************************************/
function get_db_data($key, $db_file_path, &$value)
{
    global $err_msg;

    /* ファイルの読み込み権チェック */
    $ret = is_readable_file($db_file_path);
    if ($ret === FALSE) {
        return FALSE;
    }

    /* データベースのオープン */
    $dbh = dba_popen($db_file_path, "r", DB_HANDLER);
    if ($dbh === FALSE) {
        $err_msg = "ファイルのオープンに失敗しました。(" . $db_file_path . ")";
        return FALSE;
    }

    /* データ取得 */
    $value = dba_fetch($key, $dbh);
    if ($ret === FALSE) {
        $err_msg = "データベースの検索に失敗しました。(" . $dbpath . ")";
        dba_close($dbh);
        return FALSE;
    }

    dba_close($dbh);
    return TRUE;
}
/*********************************************************
 * get_one_dbdata
 *
 * データ取得
 *
 * [引数]
 *	$key		検索キー
 *	$data		取得データ(連想配列・参照渡し)
 *
 * [返り値]
 *	TRUE		正常
 *	FALSE		異常
 **********************************************************/
function get_one_dbdata($key, &$data)
{
    global $db_file;
    global $ex_db_file;
    global $samma_conf;

    /* 受信者DBデータ取得 */
    $ret = get_db_data($key, $db_file, $value);
    if ($ret === FALSE) {
        return FALSE;
    }

    /* データ整形 */
    $domain = $key;
    $rule = EFFECT;
    $password = RANDOM;
    $indivipass = "";
    $ex_value = "";

    /* 対象or非対象 */
    $str = explode("!", $key, 2);
    if ($str[0] != $key) {
        $domain = $str[1];
        $rule = INEFFECT;
    }

    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* 拡張子DBデータ取得 */
        $ret = get_db_data($domain, $ex_db_file, $ex_value);
        if ($ret === FALSE) {
            return FALSE;
        }
    }

    /* パスワード */
    if ($value != "") {
        $password = INDIVI;
        $indivipass = $value;
    }

    /* 表示用配列へ代入 */
    $data["disp_dom"] = $domain;
    $data["domain"] = $key;
    $data["rule"] = $rule;
    $data["password"] = $password;
    $data["indivipass"] = $indivipass;
    $data["extension"] = $ex_value;

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

/* 初期表示 */
$mod_data = array();
if (isset($_POST["domname"]) === TRUE) {
    $key_domain = $_POST["domname"];
}
if (get_one_dbdata($key_domain, $mod_data) === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
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
    if (isset($samma_conf["extensiondb"]) === TRUE) {
        $mod_data["extension"] = $_POST["extension"];
    }

    /* 入力チェック */
    if (check_rcptmod_data($mod_data) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* DB更新 */
        $ret = mod_rcpt_dbdata($mod_data, $old_rule);

        /* 更新失敗はシステムエラー */
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        /* データなし・変更後データが存在は通常エラー */
        } elseif ($ret === EXTENSION_ERR) {
            $err_msg = "拡張子設定は" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
        /* データなし・変更後データが存在は通常エラー */
        } elseif ($ret === FAIL_NO_EXIST || $ret === FAIL_EXIST) {
            $err_msg = "受信者設定は" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
        /* 成功 */
        } else {
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
    $err_msg = "";
    $success_flag = 1;

    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* 拡張子DBから削除 */
        $ret = extension_db_del($ex_db_file, $del_dom);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "拡張子の" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            $success_flag = 0;
        }
    }

    if ($success_flag === 1) {
        /* 受信者DBから削除 */
        $ret = db_del($db_file, $del_dom);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            $sys_err = TRUE;
            $pg->display(NULL);
            exit (1);
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "受信者設定の" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);

        } else {
            $err_msg = "受信者設定の" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);

            /* 一覧画面へ */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
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
