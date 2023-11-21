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
 * �����ɥᥤ�������ѹ�����
 *
 * $RCSfile: mod.php,v $
 * $Revision: 1.12 $
 * $Date: 2013/08/30 06:20:25 $
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

define("OPERATION",  "�����ɥᥤ�������ѹ�");
define("TMPLFILE", "samma/samma_admin_sender_mod.tmpl");

/***********************************************************
 * set_tag_data()
 *
 * �֤����������ξ���򥻥åȤ���
 *
 * [����]
 *      $tag  �֤���������������
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function set_tag_data(&$tag) {
    global $sesskey;
    global $sender_name;
    global $status;

    /* ������� */
    $domain = $sender_name;
    $rule1 = "checked";
    $rule0 = "";
    $delete_list = "";
    $old_status = 1;

    /* �Ź沽�롼����� */
    $str = explode("!", $domain, 2);
    if ($str[0] != $domain) {
        $domain = $str[1];
        $rule1 = "";
        $rule0 = "checked";
        $old_status = 0;
    }

    /* ���������� */
    $domain = escape_html($domain);

    $tag["<<DOMAIN>>"] = $domain;
    $tag["<<SENDER_NAME>>"] = $sender_name;
    $tag["<<OLD_STATUS>>"] = $old_status;
    $tag["<<RULE_RADIO_ON>>"] = $rule1;
    $tag["<<RULE_RADIO_OFF>>"] = $rule0;

    return TRUE;
}

/*********************************************************
 * check_sendermod_dbdata
 *
 * �ѹ��ǡ����Υ����å�
 *
 * [����]
 *      $mod_data       �ѹ��ǡ���(�����Ϥ�)
 *      $status         �饸���ܥ�����
 *
 * [�֤���]
 *      TRUE            ����
 *      FALSE           �۾�
 **********************************************************/
function check_sendermod_dbdata(&$mod_data, $status)
{
    global $err_msg;

    /* �����ɥᥤ�����ѹ� */
    $str = explode("!", $mod_data, 2);
    if ($str[0] != $mod_data) {
        $mod_data = $str[1];
    }

    /* �Ź沽�롼����������å� */
    if (check_flg($status) === FALSE) {
        $err_msg = "�ѥ���ɤη����������Ǥ���";
        return FALSE;
    }

    return TRUE;
}

/*********************************************************
 * mod_sender_dbdata
 *
 * �ǡ������ɲ�
 *
 * [����]
 *      $mod_data       �ɲåǡ���
 *      $status         �饸���ܥ�����
 *      $old_status     �ѹ����Υ饸���ܥ�����
 *
 * [�֤���]
 *      SUCCESS         ����
 *      FAIL            �۾�
 *      FAIL_EXIST      �۾�(���˥ǡ�������)
 **********************************************************/
function mod_sender_dbdata($mod_data, $status, $old_status)
{
    global $db_file;

    /* �ѹ������뤫 */
    if ($status == $old_status) {
        /* �ѹ����ʤ����������return���� */
        return SUCCESS;
    }

    /* �᡼�륢�ɥ쥹����(�ѹ���) */
    if ($status == 0) {
        $new_domain = "!" . $mod_data;
    } else {
        $new_domain = $mod_data;
    }

    /* �᡼�륢�ɥ쥹����(�ѹ���) */
    if ($old_status == 0) {
        $old_domain = "!" . $mod_data;
    } else {
        $old_domain = $mod_data;
    }

    /* �ɥᥤ��/�᡼�륢�ɥ쥹���ѹ� */
    $ret = db_key_mod($db_file, $old_domain, $new_domain, "");
    if ($ret !== SUCCESS) {
        return $ret;
    }

    return SUCCESS;
}

/***********************************************************
 * �������
 **********************************************************/
/* �ѿ��ν���� */
$err_msg = "";
$sender_name = "";
$status = "";
$old_status = "";
$del_list = "";
$tag = array();

/* POST�ͤ����� */
if (isset($_POST["sender_name"]) === TRUE) {
    $sender_name = $_POST["sender_name"];
}
if (isset($_POST["status"]) === TRUE) {
    $status = $_POST["status"];
}
if (isset($_POST["old_status"]) === TRUE) {
    $old_status = $_POST["old_status"];
}
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}
/* ���å���󥭡����ѿ������� */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* ����ե����롦���ִ����ե������ɹ������å��������å� */
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

/***********************************************************
 * main����
 **********************************************************/

/* DB�ե�����ѥ����� */
$db_file = "";
if (isset($samma_conf["senderdb"]) === TRUE) {
    $files = explode(":", $samma_conf["senderdb"], 2);
    $db_file = $files[1];
} else {
    $err_msg = "DB�ե����뤬���ꤵ��Ƥ��ޤ���";
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display(CONTENT);
    exit (1);
}

/* �ѹ��ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["mod"]) === TRUE) {

    /* ���ϥǡ������ͥ����å� */
    $ret = check_sendermod_dbdata($sender_name, $status);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* �ɥᥤ��/�᡼�륢�ɥ쥹���ѹ� */
        $ret = mod_sender_dbdata($sender_name, $status, $old_status);
        if ($ret === FAIL_EXIST || $ret === FAIL_NO_EXIST) {
            $err_msg = "�����ɥᥤ�������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } else {
            /* ���ｪλ���� */
            $err_msg =  "�����ɥᥤ������򹹿����ޤ�����(" . $sender_name . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* �����ɥᥤ������������̤� */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
    }

/* ����ܥ��󤬲����줿�Ȥ� */
} elseif (isset($_POST["del"]) === TRUE) {

    /* �᡼�륢�ɥ쥹���� */
    $list[0] = $sender_name;

    /* �ɥᥤ��/�᡼�륢�ɥ쥹�κ�� */
    $ret = db_del($db_file, $list);
    if ($ret === FAIL) {
        result_log(OPERATION . ":NG:" . $err_msg);
        syserr_display();
        exit (1);
    } elseif ($ret === FAIL_DEL) {
        $err_msg = "�����ɥᥤ�������" . $err_msg;
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {

        /* ���ｪλ���� */
        $err_msg = "�����ɥᥤ�������" . $suc_msg;
        result_log(OPERATION . ":OK:" . $err_msg);

        /* �����ɥᥤ������������̤� */
        dgp_location("index.php", $err_msg);
        exit (0);
    }

/* ����󥻥�ܥ��󤬲����줿�Ȥ� */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* �����ɥᥤ������������̤� */
    dgp_location("index.php");
    exit (0);
}

/***********************************************************
 * ɽ������
 **********************************************************/
/* ���̤Υ������� */
set_tag_common($tag);

/* body��ɽ������ */
set_tag_data($tag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
