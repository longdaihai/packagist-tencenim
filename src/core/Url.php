<?php
// +------------------------------------------------------------
// | Author: HanSheng
// +------------------------------------------------------------
namespace longdaihai\tencenim\core;

class IM_Core_Url
{
    /**
     * IMsdkappid
     * @var
     */
    private $sdkappid;

    /**
     * 管理员账号
     * @var
     */
    private $identifier;

    /**
     * @var string
     */
    private $postfix;

    public function __construct($sdkappid, $identifier, $usersig)
    {
        $this->sdkappid = $sdkappid;
        $this->identifier = $identifier;

        $this->postfix = '?sdkappid='.$this->sdkappid
            .'&identifier='.$this->identifier
            .'&usersig='.$usersig
            .'&random='.$this->nonce_str()
            .'&contenttype=json';
    }

    /**
     * 导入单个帐号
     * @return string
     */
    public function setUserUrl()
    {
        return 'https://console.tim.qq.com/v4/im_open_login_svc/account_import'.$this->postfix;
    }

    /**
     * 查询帐号
     * @return string
     */
    public function accountCheckUrl()
    {
        return 'https://console.tim.qq.com/v4/im_open_login_svc/account_check'.$this->postfix;
    }

    /**
     * 单发单聊消息
     * @return string
     */
    public function sendMsgUrl()
    {
        return 'https://console.tim.qq.com/v4/openim/sendmsg'.$this->postfix;
    }

    /**
     * 创建群组
     * @return string
     */
    public function crateGroupUrl()
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/create_group'.$this->postfix;
    }

    /**
     * 增加群成员
     * @return string
     */
    public function addGroupMemberUrl()
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/add_group_member'.$this->postfix;
    }

    /**
     * 在群组中发送系统通知
     */
    public function sendGroupSystemNotificationUrl():string
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/send_group_system_notification'.$this->postfix;
    }

    /**
     * 群组中发送普通消息
     * @return string
     */
    public function sendGroupMsgUrl()
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/send_group_msg'.$this->postfix;
    }

    /**
     * 退出群组
     * @return string
     */
    public function deleteGroupMemberUrl()
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/delete_group_member'.$this->postfix;
    }

    /**
     * 解散群组
     * @return string
     */
    public function destroyGroupUrl()
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/destroy_group'.$this->postfix;
    }

    /**
     * 拉取好友
     * @return string
     */
    public function friendGetUrl()
    {
        return 'https://console.tim.qq.com/v4/sns/friend_get'.$this->postfix;
    }

    /**
     * 校验好友
     * @return string
     */
    public function friendCheckUrl()
    {
        return 'https://console.tim.qq.com/v4/sns/friend_check'.$this->postfix;
    }


    /**
     * 随机32位字符串 纯数字
     * @param int $num
     * @return string
     */
    public function nonce_str($num = 32){
        $result = '';
        $str = '0123456789';
        for ($i = 0; $i < $num; $i++) {
            $result .= $str[rand(0,9)];
        }
        return $result;
    }

    /**
     * 获取群成员详细资料
     * @return string
     */
    public function getGroupMemberInfoUrl()
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/get_group_member_info'.$this->postfix;
    }

    /**
     * 设置用户资料
     * @return string
     */
    public function portraitSetUrl()
    {
        return 'https://console.tim.qq.com/v4/profile/portrait_set'.$this->postfix;
    }

    /**
     * @return string
     */
    public function friendAddUrl()
    {
        return 'https://console.tim.qq.com/v4/sns/friend_add'.$this->postfix;
    }

    /**
     * 获取 App 中的所有群组
     * @return string
     */
    public function getGroupListUrl()
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/get_appid_group_list'.$this->postfix;
    }

    /**
     * 删除好友
     * @return string
     */
    public function friendDelete()
    {
        return 'https://console.tim.qq.com/v4/sns/friend_delete'.$this->postfix;
    }

    /**
     * 获取群详细资料
     * @return string
     */
    public function getGroupInfoUrl()
    {
        return 'https://console.tim.qq.com/v4/group_open_http_svc/get_group_info'.$this->postfix;
    }


}