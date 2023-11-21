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
 * SaMMA�����԰�������
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
�ƥڡ����������
*********************************************************/

define("OPERATION", "�������������");
define("TMPLFILE", "samma/samma_admin_rcpt_menu.tmpl");

/*********************************************************
 * print_result
 *
 * �������ɽ������
 *
 * [����]
 *       �ʤ�
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function print_result(&$looptag)
{
    global $rp_data;
    global $ex_data;
    global $del_list;
    global $samma_conf;

    /* �ɥᥤ��� */
    $domain_count = 0;

    /* �ǡ����ʤ��϶�ɽ�� */
    if (is_array($rp_data) === FALSE) {
        $looptag = array();
        return;
    }

    /* ������ */
    uksort($rp_data, DOMAIN_SORT);

    /* ɽ�� */
    foreach ($rp_data as $key => $value) {

        /* ������� */
        $rule = DISP_EFFECT;
        $passwd = DISP_RANDOM;
        $domain = $key;
        $extension = "";

        /* �ѥ���� */
        if ($value != "") {
            $passwd = DISP_INDIVI;
        }

        /* �Ź沽�롼����� */
        $str = explode("!", $key, 2);
        if ($str[0] != $key) {
            $domain = $str[1];
            $rule = DISP_INEFFECT;
            $passwd = DISP_NOPASS;
        }

        /* ������DB�ˤ��äƳ�ĥ��DB�ˤ⤢���� */
        if (isset($samma_conf["extensiondb"]) === TRUE) {
            if (isset($ex_data["$domain"]) === TRUE) {

                # �����ǳ�ĥ�Ҥ�value�����ꤹ��
                $extension = $ex_data["$domain"];
            }
        }

        /* ���������� */
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
 * �������
 **********************************************************/

/* �ͤν���� */
$tag = array();
$looptag = array();

/* ���å���󥭡����ѿ������� */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* ����ե����륿�ִ����ե������ɹ������å����Υ����å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* SaMMA����ե������ɹ��� */
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaconf"]);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* ��å��������� */
if (isset($_POST["msg"]) === TRUE) {
    $err_msg = escape_html($_POST["msg"]);
}

/***********************************************************
 * main����
 **********************************************************/
/* DB�ե�������� */
$db_file = "";
if (isset($samma_conf["rcptdb"]) === TRUE) {
    $files = explode(":", $samma_conf["rcptdb"], 2);
    $db_file = $files[1];
} else {
    $err_msg = "DB�ե����뤬���ꤵ��Ƥ��ޤ���";
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}
    
/* ��ĥ��DB�ե�������� */
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

/* �ݻ����ͼ��� */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

/* ������ʬ�� */
/* �����ɲ� */
if (isset($_POST["new_add"]) === TRUE) {
    /* �ɲò��̤����� */
    dgp_location("add.php");
    exit (0);

/* �����å�������Τ��� */
} elseif (isset($_POST["check_del"]) === TRUE) {
    /* �����å��ʤ� */
    if (isset($_POST["delete"]) === FALSE) {
        $err_msg = "����оݤ����򤵤�Ƥ��ޤ���";
    } else {
        /* �ɥᥤ��/�᡼�륢�ɥ쥹�κ�� */
        $ret = db_del($db_file, $_POST["delete"]);
        /* �������(�����ƥ२�顼) */
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        /* ������� */
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "�����������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            /* ����������н��� */
            if ($suc_msg != "") {
                $suc_msg = "�����������" . $suc_msg;
                result_log(OPERATION . ":OK:" . $suc_msg);
                $err_msg .= "<br>" . $suc_msg;
            }
        /* ���� */
        } else {
            $err_msg = "�����������" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);
        }
        $db_err_msg = $err_msg;

        /* ��ĥ�Ҥκ�� */
        if (isset($samma_conf["extensiondb"]) === TRUE) {
            $ret = extension_db_del($ex_db_file, $_POST["delete"]);
            /* �������(�����ƥ२�顼) */
            if ($ret === FAIL) {
                result_log(OPERATION . ":NG:" . $err_msg);
                syserr_display();
                exit (1);
            /* ������� */
            } elseif ($ret === FAIL_DEL) {
                $err_msg = "��ĥ�������" . $err_msg;
                result_log(OPERATION . ":NG:" . $err_msg);
                $err_msg = $db_err_msg . "<br>" . $err_msg;
                /* ����������н��� */
                if ($suc_msg != "") {
                    $suc_msg = "��ĥ�������" . $suc_msg;
                    result_log(OPERATION . ":OK:" . $suc_msg);
                    $err_msg .= "<br>" . $suc_msg;
                }
            } elseif ($ret === NO_CHANGE) {
                /* �ʤˤ���Ϥ��ʤ� */
            /* ���� */
            } else {
                $err_msg = "��ĥ�������" . $suc_msg;
                result_log(OPERATION . ":OK:" . $err_msg);
                $err_msg = $db_err_msg . "<br>" . $err_msg;
            }
        }
    }
}

/* ������������� */
$ret = db_search($db_file, $rp_data);
if ($ret === FAIL) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* ��ĥ��������� */
if (isset($samma_conf["extensiondb"]) === TRUE) {
    $ret = db_search($ex_db_file, $ex_data);
    if ($ret === FAIL) {
        result_log(OPERATION . ":NG:" . $err_msg);
        syserr_display();
        exit (1);
    }
}

/***********************************************************
 * ɽ������
 **********************************************************/
/* ���̤Υ������� */
set_tag_common($tag);

print_result($looptag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, $looptag, STARTTAG, ENDTAG);
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
