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
 * $Revision: 1.7 $
 * $Date: 2013/08/30 06:06:35 $
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

define("OPERATION", "�桼���μ����������ѹ�");
define("TMPLFILE", "samma/samma_user_rcpt_mod.tmpl");

/*********************************************************
 * display_body
 *
 * hidden���Ϥ��ǡ������֤�����
 *
 * [����]
 *       $tag���֤���������
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function display_body(&$tag) {
    global $sesskey;
    global $del_list;
    global $mod_data;
    global $dispusr;
    global $user;
    global $mode;
    global $ldapdata;
    global $userdn;
    global $form_name;

    /* DN�ΰŹ沽 */
    $userdn = base64_encode($userdn);
    $userdn = str_rot13($userdn);

    /* �����桼��̾�ΰŹ沽 */
    $form_name = base64_encode($form_name);
    $form_name = str_rot13($form_name);

    /* hidden���Ϥ��ǡ������Ǽ */
    $hiddendata['dn'] = $userdn;
    $hiddendata['sk'] = $sesskey;
    $hiddendata['page'] = $_POST["page"];
    $hiddendata['filter'] = $_POST["filter"];
    $hiddendata['form_name'] = $form_name;
    $hiddendata['name_match'] = $_POST['name_match'];

    /* �桼��̾ */
    $user = escape_html($dispusr);

    /* �ɥᥤ��̾ */
    $domain = "";
    if (isset($mod_data["domain"]) === TRUE) {
        $domain = escape_html($mod_data["domain"]);
    }

    $disp_dom = "";
    if (isset($mod_data["disp_dom"]) === TRUE) {
        $disp_dom = escape_html($mod_data["disp_dom"]);
    }

    /* �ѥ���� */
    $pass_radio_r = "checked";
    $pass_radio_i = "";

    if (isset($mod_data["password"]) === TRUE) {
        if ($mod_data["password"] == 0) {
            $pass_radio_r = "";
            $pass_radio_i = "checked";
        }
    }

    /* ���̥ѥ���� */
    $indivipass = "";
    if (isset($mod_data["indivipass"]) === TRUE) {
        $indivipass = escape_html($mod_data["indivipass"]);
    }

    /* �Ź沽�롼�� */
    $rule_radio_on = "checked";
    $rule_radio_off = "";

    if (isset($mod_data["rule"]) === TRUE) {
        if ($mod_data["rule"] == 0) {
            $rule_radio_on = "";
            $rule_radio_off = "checked";
        }
    }

    /* �ݻ����������� */
    $hidden_del = "";
    if (is_array($del_list) === TRUE) {
        foreach ($del_list as $delval) {
            $hidden_del .=<<<EOD
  <input type="hidden" name="delete[]" value="$delval">
EOD;
        }
    }

    /* hidden */
    $hidden_data = "";
    foreach($hiddendata as $hidkey => $hidval) {
        $hidval = escape_html($hidval);
        $hidden_data .=<<<EOD
  <input type="hidden" name="{$hidkey}" value="{$hidval}">
EOD;
    }

    $tag["<<USER>>"] = $user;
    $tag["<<DISP_DOM>>"] = $disp_dom;
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
 * �������
 **********************************************************/

/* �ѿ��ν���� */
$tag = array();

/* �桼�������Ǽ */
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
/* ���å���󥭡����ѿ������� */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/
/* �ڡ����η��������å� */
if (is_num_check($page) === FALSE) {
    $err_msg = "�ڡ������ͤ������Ǥ���";
    syserr_display();
    exit (1);
}
/* �ե��륿��ʣ�粽 */
if (sess_key_decode($filter, $dec_filter) === FALSE) {
    syserr_display();
    exit (1);
}

/* �ե��륿�η��������å� */
$fdata = explode(':', $dec_filter);
if (count($fdata) != 3) {
    $err_msg = "�ե��륿�η����������Ǥ���";
    syserr_display();
    exit (1);
}

/* DN�η��������å� */
$len = (-1) * strlen($web_conf[$url_data['script']]['ldapbasedn']);
$cmpdn = substr($userdn, $len);
if (strcmp($cmpdn, $web_conf[$url_data['script']]['ldapbasedn']) != 0) {
    $err_msg = "DN�η����������Ǥ���";
    syserr_display();
    exit (1);
}

/* �桼������μ��� */
$ret = get_userdata($userdn);
if ($ret !== TRUE) {
    if ((isset($_POST['delete']) === TRUE) && ($ret !== LDAP_ERR_BIND)) {
        $err_msg = "���ꤵ�줿�桼���Ϥ��Ǥ˺������Ƥ��ޤ���";
    }
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

$user = $ldapdata[0]["uid"][0];

$dispattr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = $ldapdata[0][$dispattr][0];

/* �ե���������Ǽ */
$form_name = $_POST["form_name"];
$name_match = $_POST["name_match"];

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
        /* ���� */
        $ret = mod_rcpt_data($mod_data, $old_rule);

        /* �����ƥ२�顼 */
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        /* �������� */
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
        } else {
            /* ���� */
            $err_msg = "����������ι������������ޤ�����(" . $mod_data["disp_dom"] . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* �������̤� */
            page_location_search("samma_list.php", $err_msg, $del_list);
            exit (0);
        }

    }

/* ��� */
} elseif (isset($_POST["del"]) === TRUE) {
    /* ����оݺ��� */
    $del_dom[] = $mod_data["domain"];

    /* ��� */
    $ret = ldap_enc_del($userdn, $del_dom);
    /* ����оݤ����ʤ����顼���̾泌�顼 */
    if ($ret === LDAP_ERR_NODATA) {
        $err_msg = "�����������" . $err_msg;
        result_log(OPERATION . ":NG:" . $err_msg);
    /* ���顼�ϥ����ƥ२�顼 */
    } elseif ($ret !== LDAP_OK) {
        result_log(OPERATION . ":NG:" . $err_msg);
        syserr_display(CONTENT);
        exit (1);
    /* ���� */
    } else {
        $err_msg = "�����������" . $suc_msg;
        result_log(OPERATION . ":OK:" . $err_msg);

        /* �������̤� */
        page_location_search("samma_list.php", $err_msg, $del_list);
        exit (0);
    }

/* ����󥻥� */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* �������̤����� */
    page_location_search("samma_list.php");
    exit (0);

}


/***********************************************************
 * ɽ������
 **********************************************************/

/* ���̤Υ������� */
set_tag_common($tag);

display_body($tag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
