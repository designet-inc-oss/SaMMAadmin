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
 * SaMMA�������ѹ�����
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
�ƥڡ����������
*********************************************************/

define("OPERATION", "�����������ѹ�");
define("TMPLFILE", "samma/samma_user_mod.tmpl");

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
    global $del_list;
    global $mod_data;
    global $dispusr;

    /* �桼��̾ */
    $user = escape_html($dispusr);
    $tag["<<USER>>"] = $user;

    /* �ɥᥤ��̾ */
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

    /* �ѥ���� */
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

    /* ���̥ѥ���� */
    $indivipass = "";
    if (isset($mod_data["indivipass"]) === TRUE) {
        $indivipass = escape_html($mod_data["indivipass"]);
    }
    $tag["<<INDIVIPASS>>"] = $indivipass;

    /* �Ź沽�롼�� */
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
    $pg->display(NULL);
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
    syserr_display();
    exit (1);
}

$dispusr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = escape_html($ldapdata[0][$dispusr][0]);

/* �ݻ����ͼ��� */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

/* ���ɽ�� */
$mod_data = array();
if (isset($_POST["domname"]) === TRUE) {
    $key_domain = $_POST["domname"];
}
if (get_one_data($key_domain, $mod_data) === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    $err_msg = "�������������μ����˼��Ԥ��ޤ�����";
    $sys_err = TRUE;
    syserr_display();
    exit (1);
}

/* ������ʬ�� */
/* �ѹ� */
if (isset($_POST["mod"]) === TRUE) {
    /* ���ߤΥ롼������ */
    $old_rule = $mod_data["rule"];

    /* �ͼ��� */
    $mod_data["password"] = $_POST["password"];
    $mod_data["rule"] = $_POST["rule"];
    $mod_data["indivipass"] = $_POST["indivipass"];

    /* ���ϥ����å� */
    if (check_rcptmod_data($mod_data) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* �桼���ǤΥХ����̵�� */
        $env['user_self'] = FALSE;

        /* ���� */
        $ret = mod_rcpt_data($mod_data, $old_rule);

        /* �����ƥ२�顼 */
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* �桼���ѥ�å������ѹ� */
            $err_msg = "����������ι����˼��Ԥ��ޤ�����(" . $mod_data["disp_dom"] . ")";
            $sys_err = TRUE;
            syserr_display();
            exit (1);
        /* �������� */
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* �桼���ѥ�å������ѹ� */
            $err_msg = "����������ι����˼��Ԥ��ޤ�����(" . $mod_data["disp_dom"] . ")";
        } else {
            /* ���� */
            $err_msg = "����������ι������������ޤ�����(" . $mod_data["disp_dom"] . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* �������̤� */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
    }
/* ��� */
} elseif (isset($_POST["del"]) === TRUE) {
    /* ����оݺ��� */
    $del_dom[] = $mod_data["domain"];

    /* �桼���ǤΥХ����̵�� */
    $env['user_self'] = FALSE;

    /* ��� */
    $ret = ldap_enc_del($userdn, $del_dom);
    /* ����оݤ����ʤ����顼���̾泌�顼 */
    if ($ret === LDAP_ERR_NODATA) {
        $err_msg = "�����������" . $err_msg;
        result_log(OPERATION . ":NG:" . $err_msg);
    /* ���顼�ϥ����ƥ२�顼 */
    } elseif ($ret !== LDAP_OK) {
        result_log(OPERATION . ":NG:" . $err_msg);
        /* �桼���ѥ�å������ѹ� */
        $err_msg = "����������κ���˼��Ԥ��ޤ�����(" . $mod_data["domain"] . ")";
        $sys_err = TRUE;
        syserr_display();
        exit (1);
    /* ���� */
    } else {
        $err_msg = "�����������" . $suc_msg;
        result_log(OPERATION . ":OK:" . $err_msg);

        /* �������̤� */
        dgp_location("index.php", $err_msg);
        exit (0);
    }
/* ����󥻥� */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* �������̤����� */
    dgp_location("index.php");
    exit (0);

}


/***********************************************************
 * ɽ������
 **********************************************************/
/* ���̤Υ������� */
set_tag_common($tag);

set_tag_data($tag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
