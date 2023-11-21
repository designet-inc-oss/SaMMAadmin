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
 * SaMMA�������ɲò���
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
�ƥڡ����������
*********************************************************/

define("OPERATION", "�����������ɲ�");
define("TMPLFILE", "samma/samma_user_add.tmpl");

/***********************************************************
 * �ؿ�
 **********************************************************/
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
    global $dispusr;
    global $del_list;
    global $add_data;

    /* �桼��̾ */
    $user = escape_html($dispusr);
    $tag["<<USER>>"] = $user;

    /* �ɥᥤ��̾ */
    $domain = "";
    if (isset($add_data["domain"]) === TRUE) {
        $domain = escape_html($add_data["domain"]);
    }
    $tag["<<DOMAIN>>"] = $domain;

    /* �ѥ���� */
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

    /* ���̥ѥ���� */
    $indivipass = "";
    if (isset($add_data["indivipass"]) === TRUE) {
        $indivipass = escape_html($add_data["indivipass"]);
    }
    $tag["<<INDIVIPASS>>"] = $indivipass;

    /* �Ź沽�롼�� */
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
 * �������
 **********************************************************/
$tag = array();

/* ���å���󥭡����ѿ������� */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = user_init();
if ($ret === FALSE) {
    $sys_err = TRUE;
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/
/* �桼��̾��Ǽ */
$user = $env['loginuser'];
$userdn = $env['user_selfdn'];

/* �桼������μ��� */
$ret = get_userdata ($userdn);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    $err_msg = "�桼������μ����˼��Ԥ��ޤ�����";
    $sys_err = TRUE;
    $pg->display(NULL);
    exit (1);
}

$dispusr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = escape_html($ldapdata[0][$dispusr][0]);

/* �ݻ����ͼ��� */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

/* ������ʬ�� */
/* �ɲ� */
if (isset($_POST["add"]) === TRUE) {
    /* �ͼ��� */
    $add_data["domain"] = $_POST["adddomain"];
    $add_data["password"] = $_POST["password"];
    $add_data["rule"] = $_POST["rule"];
    $add_data["indivipass"] = $_POST["indivipass"];

    /* ���ϥ����å� */
    if (check_rcptadd_data($add_data) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* �桼���ǤΥХ����̵�� */
        $env['user_self'] = FALSE;

        /* LDAP��Ͽ */
        $ret = add_rcpt_data($add_data);
        /* ���ˤ���ǡ�����Ͽ���̾泌�顼 */
        if ($ret === FAIL_EXIST) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* ���顼��å������ѹ� */
            $err_msg = "������������ɲä˼��Ԥ��ޤ�����(" .  $add_data["domain"] . ")";
        /* ��Ͽ���Ԥϥ����ƥ२�顼 */
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* ���顼��å������ѹ� */
            $err_msg = "������������ɲä˼��Ԥ��ޤ�����(" .  $add_data["domain"] . ")";
            $sys_err = TRUE;
            syserr_display();
            exit (1);
        /* ���� */
        } else {
            $err_msg = "������������ɲä��ޤ�����(" . $add_data["domain"] . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* �������̤� */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
    }
/* ����󥻥� */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* �ɲò��̤����� */
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
