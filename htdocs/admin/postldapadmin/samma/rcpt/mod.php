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
�ƥڡ����������
*********************************************************/

define("OPERATION", "�����������ѹ�");
define("TMPLFILE", "samma/samma_admin_rcpt_mod.tmpl");

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
 *      $old_command  �ѹ������ޥ��
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function set_tag_data(&$tag, $old_command) {
    global $sesskey;
    global $del_list;
    global $mod_data;
    global $samma_conf;
    global $web_conf;

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

    $extension = "";
    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* ��ĥ�� */
        if (isset($mod_data["extension"]) === TRUE) {
            $extension = escape_html($mod_data["extension"]);
        }
    }

    $command = "";
    $com_name = "";
    if (isset($samma_conf["commanddb"]) === TRUE) {
        $ret = read_command_conf($web_conf["postldapadmin"]["commandconf"], $command_list);
        if ($ret === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        }

        /* ���ޥ�ɤ�����ե�����ΰ�������������� */
        $html_command = "<option value=\"$command\">$com_name";

        /* ���ޥ�ɤΰ����ˤʤ����ޥ�ɤ��ϤäƤ������no_change */
        if (isset($mod_data["command"]) === TRUE){
            $mod_command = $mod_data["command"];
            if(!isset($command_list[$mod_command])) {
                $command = "no_change";
                $com_name = escape_html($mod_command);
                $html_command .= "<option value=\"$command\" selected>$com_name";

                /* old_command�����ʤ���ʤΤ�$com_name������ */
                if ($old_command === "") {
                    $old_command = $com_name;
                }
            } elseif ($old_command !== "") {
                $command = "no_change";
                $html_command .= "<option value=\"$command\">$old_command";
            }
        }

        foreach($command_list as $command=>$com_name) {
            /* ���ޥ�ɤ��ϤäƤ������ */
            if (isset($mod_data["command"]) && ($mod_data["command"] === $command)) {
                $command = escape_html($mod_data["command"]);
                $com_name = escape_html($com_name);
                $html_command .= "<option value=\"$command\" selected>$com_name";
            } else {
                $command = escape_html($command);
                $com_name = escape_html($com_name);
                $html_command .= "<option value=\"$command\">$com_name";
            }
        }
    }

    $tag["<<DISP_DOM>>"] = $disp_dom;
    $tag["<<DOMAIN>>"] = $domain;
    $tag["<<PASS_RADIO_R>>"] = $pass_radio_r;
    $tag["<<PASS_RADIO_I>>"] = $pass_radio_i;
    $tag["<<INDIVIPASS>>"] = $indivipass;
    $tag["<<EXTENSION>>"] = $extension;
    $tag["<<HTML_COMMAND>>"] = $html_command;
    $tag["<<RULE_RADIO_ON>>"] = $rule_radio_on;
    $tag["<<RULE_RADIO_OFF>>"] = $rule_radio_off;
    $tag["<<OLD_COMMAND>>"] = $old_command;

    return TRUE;

}

/*********************************************************
 * mod_rcpt_dbdata
 *
 * �ǡ����ν���
 *
 * [����]
 *	$mod_data	�ѹ��ǡ���
 *	$old_rule	�ѹ����Ź沽�롼��
 *
 * [�֤���]
 *	SUCCESS		����
 *	FAIL		�۾�
 *	FAIL_NO_EXIST	�۾�(�ǡ����ʤ�)
 *	FAIL_EXIST	�۾�(�ǡ������ˤ���)
 **********************************************************/
function mod_rcpt_dbdata($mod_data,  $old_rule)
{
    global $db_file;
    global $ex_db_file;
    global $com_db_file;
    global $samma_conf;

    /* ��Ͽ�ǡ������� */
    $old_key = $mod_data["domain"];

    /* �оݤ������оݤ��ѹ� */
    if ($old_rule == 1 && $mod_data["rule"] == 0) {
        $key = "!" . $old_key;
    /* ���оݤ����оݤ��ѹ� */
    } elseif ($old_rule == 0 && $mod_data["rule"] == 1) {
        $str = explode("!", $mod_data["domain"], 2);
        $key = $str[1];
    /* �ѹ��ʤ� */
    } else {
        $key = $old_key;
    }

    /* �ѥ���� */
    $value = "";
    if ($mod_data["password"] == 0) {
        $value = $mod_data["indivipass"];
    }

    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* ��ĥ�Ҥ����ʤ��ĥ��DB������ */
        if ($mod_data["extension"] == "") {
            $del_dom[] = $mod_data["domain"];
            $ret = extension_db_del($ex_db_file, $del_dom);
            if ($ret === NO_CHANGE) {
                /* �ʤˤ⤷�ʤ� */
            } elseif ($ret === FAIL) {
                /* �����ƥ२�顼 */
                return $ret;
            } elseif ($ret !== SUCCESS) {
                /* ��ĥ�ҤΥ��顼 */
                return EXTENSION_ERR;
            }
        } else {
            /* ��ĥ��DB���� */
            $ret = extension_db_mod($ex_db_file, $ex_db_type, $old_key, $mod_data["extension"]);
            if ($ret === NO_CHANGE) {
                /* �ʤˤ⤷�ʤ� */
            } elseif ($ret === FAIL) {
                /* �����ƥ२�顼 */
                return $ret;
            } elseif ($ret !== SUCCESS) {
                /* ��ĥ�ҤΥ��顼 */
                return EXTENSION_ERR;
            }
        }
    }

    /* ���ޥ���ѹ�����ʤ� */
    if (isset($samma_conf["commanddb"]) === TRUE && $mod_data["command"] !== "no_change") {
        /* ���ޥ�ɤ����ʤ饳�ޥ��DB������ */
        if ($mod_data["command"] == "") {
            $del_dom[] = $mod_data["domain"];
            $ret = extension_db_del($com_db_file, $del_dom);
            if ($ret === NO_CHANGE) {
                /* �ʤˤ⤷�ʤ� */
            } elseif ($ret === FAIL) {
                /* �����ƥ२�顼 */
                return $ret;
            } elseif ($ret !== SUCCESS) {
                /* ���ޥ�ɤΥ��顼 */
                return COMMAND_ERR;
            }
        } else {
            /* ���ޥ��DB���� */
            $ret = extension_db_mod($com_db_file, $com_db_type, $old_key, $mod_data["command"]);
            if ($ret === NO_CHANGE) {
                /* �ʤˤ⤷�ʤ� */
            } elseif ($ret === FAIL) {
                /* �����ƥ२�顼 */
                return $ret;
            } elseif ($ret !== SUCCESS) {
                /* ���ޥ�ɤΥ��顼 */
                return COMMAND_ERR;
            }
        }
    }

    /* �Ź沽�롼���ѹ����ʤ����Ϥ��Τޤ��ѹ� */
    if ($old_rule == $mod_data["rule"]) {

        /* DB���� */
        $ret = db_mod($db_file, $key, $value);
        if ($ret !== SUCCESS) {
            return $ret;
        }
    } else {
        /* DB����(�������ѹ�) */
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
 * �ǡ�������
 *
 * [����]
 *	$key		��������
 *     $db_file_path   �ǡ����١����ե�����ѥ�
 *	$value		�����ǡ���(�����Ϥ�)
 *
 * [�֤���]
 *	TRUE		����
 *	FALSE		�۾�
 *	NOT_FOUND	���̵꤬��
 **********************************************************/
function get_db_data($key, $db_file_path, &$value)
{
    global $err_msg;

    /* �ե�������ɤ߹��߸������å� */
    $ret = is_readable_file($db_file_path);
    if ($ret === FALSE) {
        return FALSE;
    }

    /* �ǡ����١����Υ����ץ� */
    $dbh = dba_popen($db_file_path, "r", DB_HANDLER);
    if ($dbh === FALSE) {
        $err_msg = "�ե�����Υ����ץ�˼��Ԥ��ޤ�����(" . $db_file_path . ")";
        return FALSE;
    }

    /* �ǡ������� */
    $value = dba_fetch($key, $dbh);
    if ($value === FALSE) {
        $err_msg = "�ǡ����١����θ����˼��Ԥ��ޤ�����(" . $db_file_path . ")";
        dba_close($dbh);
        return 'NOT_FOUND';
    }

    dba_close($dbh);
    return TRUE;
}
/*********************************************************
 * get_one_dbdata
 *
 * �ǡ�������
 *
 * [����]
 *	$key		��������
 *	$data		�����ǡ���(Ϣ�����󡦻����Ϥ�)
 *
 * [�֤���]
 *	TRUE		����
 *	FALSE		�۾�
 **********************************************************/
function get_one_dbdata($key, &$data)
{
    global $db_file;
    global $ex_db_file;
    global $com_db_file;
    global $samma_conf;
    global $err_msg;

    /* ������DB�ǡ������� */
    $ret = get_db_data($key, $db_file, $value);
    if ($ret !== TRUE) {
        return FALSE;
    }

    /* �ǡ������� */
    $domain = $key;
    $rule = EFFECT;
    $password = RANDOM;
    $indivipass = "";
    $ex_value = "";
    $com_value = "";

    /* �о�or���о� */
    $str = explode("!", $key, 2);
    if ($str[0] != $key) {
        $domain = $str[1];
        $rule = INEFFECT;
    }

    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* ��ĥ��DB�ǡ������� */
        $ret = get_db_data($domain, $ex_db_file, $ex_value);
	if ($ret === TRUE) {
	    $data["extension"] = $ex_value;
	} elseif ($ret === 'NOT_FOUND') {
	    $err_msg = "";
	} else {
	    return FALSE;
	}
    }

    if (isset($samma_conf["commanddb"]) === TRUE) {
        /* ���ޥ��DB�ǡ������� */
        $ret = get_db_data($domain, $com_db_file, $com_value);
	if ($ret === TRUE) {
	    $data["command"] = $com_value;
	} elseif ($ret === 'NOT_FOUND') {
	    $err_msg = "";
	} else {
	    return FALSE;
	}
    }

    /* �ѥ���� */
    if ($value != "") {
        $password = INDIVI;
        $indivipass = $value;
    }

    /* ɽ������������� */
    $data["disp_dom"] = $domain;
    $data["domain"] = $key;
    $data["rule"] = $rule;
    $data["password"] = $password;
    $data["indivipass"] = $indivipass;

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

/* ���ޥ��DB�ե�������� */
$com_db_file = "";
$com_db_type = "";
if (isset($samma_conf["commanddb"]) === TRUE) {
    $com_files = explode(":", $samma_conf["commanddb"], 2);
    $com_db_type = $com_files[0];
    $com_db_file = $com_files[1];
    $tag["<<COMMAND_START>>"] = "";
    $tag["<<COMMAND_END>>"] = "";
} else {
    $tag["<<COMMAND_START>>"] = "<!--";
    $tag["<<COMMAND_END>>"] = "-->";
}

/* �ݻ����ͼ��� */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

$old_command = "";
if (isset($_POST["old_command"]) === TRUE) {
    $old_command = $_POST["old_command"];
}

/* ���ɽ�� */
$mod_data = array();
if (isset($_POST["domname"]) === TRUE) {
    $key_domain = $_POST["domname"];
}
if (get_one_dbdata($key_domain, $mod_data) === FALSE) {
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
    if (isset($samma_conf["extensiondb"]) === TRUE) {
        $mod_data["extension"] = $_POST["extension"];
    }
    if (isset($samma_conf["commanddb"]) === TRUE && $_POST["command"] !== "no_change") {

        /* ���ޥ�ɤΥ����å� */
        $ret = check_exist_command($web_conf["postldapadmin"]["commandconf"], $_POST["command"]);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } elseif ($ret === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } else {
            $mod_data["command"] = $_POST["command"];
        }
    }

    /* ���ϥ����å� */
    if (check_rcptmod_data($mod_data) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* DB���� */
        $ret = mod_rcpt_dbdata($mod_data, $old_rule);

        /* �������Ԥϥ����ƥ२�顼 */
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        /* �ǡ����ʤ����ѹ���ǡ�����¸�ߤ��̾泌�顼 */
        } elseif ($ret === EXTENSION_ERR) {
            $err_msg = "��ĥ�������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
        /* �ǡ����ʤ����ѹ���ǡ�����¸�ߤ��̾泌�顼 */
        } elseif ($ret === FAIL_NO_EXIST || $ret === FAIL_EXIST) {
            $err_msg = "�����������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
        /* ���� */
        } else {
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
    $err_msg = "";
    $success_flag = 1;

    if (isset($samma_conf["extensiondb"]) === TRUE) {
        /* ��ĥ��DB������ */
        $ret = extension_db_del($ex_db_file, $del_dom);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "��ĥ�Ҥ�" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            $success_flag = 0;
        }
    }

    if (isset($samma_conf["commanddb"]) === TRUE) {
        /* ���ޥ��DB������ */
        $ret = extension_db_del($com_db_file, $del_dom);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "���ޥ�ɤ�" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            $success_flag = 0;
        }
    }

    if ($success_flag === 1) {
        /* ������DB������ */
        $ret = db_del($db_file, $del_dom);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            $sys_err = TRUE;
            $pg->display(NULL);
            exit (1);
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "�����������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);

        } else {
            $err_msg = "�����������" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);

            /* �������̤� */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
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

set_tag_data($tag, $old_command);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
