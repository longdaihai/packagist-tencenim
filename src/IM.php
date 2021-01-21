<?php
/**
 * 腾讯云IM扩展类
 */
namespace longdaihai\tencenim;

class IM {

    /**
     * @var IM_Core_Url
     */
    private $urlClass;

    /**
     * sign缓存key值
     * @var string
     */
    private $cacheSignKey = 'qcloud_im_genUserSig';

    /**
     * sign过期时间
     * @var float|int
     */
    private $expire = 86400 * 7;

    private $TLSSigAPIv2 = null;

    private $identifier;

    private $sdkappid = '';

    public function __construct($config = null)
    {
        if ($config == null){
            throw new \Exception('配置为空');
        }

        if (!isset($conf['sdkappid']) || !$conf['sdkappid']){
            throw new \Exception("sdkappid 未配置",1);
        }
        if (!isset($conf['key']) || !$conf['key']){
            throw new \Exception("key 未配置");
        }
        if (!isset($conf['identifier']) || !$conf['identifier']){
            throw new \Exception("identifier 未配置");
        }
        $this->sdkappid    = $conf['sdkappid'];
        $this->identifier  = $conf['identifier'];
        $this->TLSSigAPIv2 = new IM_Core_TLSSigAPIv2($this->sdkappid, $conf['key']);
        $this->urlClass    = new IM_Core_Url($this->sdkappid, $conf['identifier'], $this->getUsersig());
    }

    /**
     * 获取签名
     * @param $identifier 默认''为配置管理员
     * @return string
     * @throws Exception
     */
    public function getUsersig($identifier = ''):string
    {
        if ($identifier == ''){
            $identifier = $this->identifier;
        }
        $sign = getCache($identifier.'_'.$this->cacheSignKey);
        if(!$sign){
            $sign = $this->TLSSigAPIv2->genUserSig($identifier, $this->expire);
            setCache($identifier.'_'.$this->cacheSignKey, $sign, $this->expire);
        }
        return $sign;
    }

    /**
     * 获取 sdkappid
     * @return string
     */
    public function getAppid():string
    {
        return $this->sdkappid;
    }

    /**
     * 创建IM用户(导入单个帐号)
     * @param $uid
     * @param $nickname
     * @param $img_url
     * @return mixed
     */
    public function setUser($uid, $nickname, $img_url = ''){
        $url = $this->urlClass->setUserUrl();
        $data_arr = [
            'Identifier'=>(string)$uid,
            'Nick'=>$nickname,
            'FaceUrl'=>$img_url
        ];
        return $this->http_request($url, $data_arr);
    }

    /**
     * 查询帐号
     * @param array $CheckItem [
     *                            ['UserID' => 'uid'],
     *                            ['UserID' => 'uid'],
     *                            ...
     *                         ]
     * @return array
     */
    public function accountCheck(array $CheckItem):array
    {
        $url = $this->urlClass->accountCheckUrl();
        $data_arr = [
            'CheckItem' => $CheckItem
        ];
        return $this->http_request($url, $data_arr);
    }

    /**
     * 设置用户资料
     * @param $From_Account
     * @param $ProfileItem  [
     *                        ['Tag'=>'Tag_Profile_IM_Nick','Value'=>'袈裟'] // 昵称
     *                      ]
     * @return bool|mixed|string
     */
    public function portraitSet($From_Account,$ProfileItem)
    {
        $url = $this->urlClass->portraitSetUrl();
        $data = [
            'From_Account' => (string)$From_Account,
            'ProfileItem' => $ProfileItem
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 单发单聊消息
     * @param string $From_Account 发送者的账号默认空为管理员
     * @param $To_Account
     * @param array $MsgBody [
                                'MsgType'=>'TIMTextElem',
                                'MsgContent'=> [
                                    'Text'=> '文本内容'
                                ];
     *                       ]
     * @param string $Type 发送类型 text纯文本
     * @param int $SyncOtherMachine 1同步至From_Account, 2不同步消息至From_Account
     * @return bool|mixed|string
     */
    public function pushMsg($From_Account='',$To_Account,$MsgBody,$Type='text',$SyncOtherMachine=1){
        $url = $this->urlClass->sendMsgUrl();

        switch ($Type){
            case 'text':
                if (is_array($MsgBody)){
                    $MsgBody = json_encode($MsgBody);
                }
                $body = [
                    'MsgType'=>'TIMTextElem',
                    'MsgContent'=> [
                        'Text'=> $MsgBody
                    ]
                ];
                break;
            default:
                $body = [];
                break;
        }
        $data_arr = [
            'SyncOtherMachine' => (int)$SyncOtherMachine,
            'To_Account' => (string)$To_Account,
            'MsgRandom' => (int)$this->urlClass->nonce_str(8), // 随机数
            'MsgTimeStamp' => time(), // 时间戳
            'MsgBody' => [$body],
        ];

        if (!empty($From_Account)) {
            $data_arr['From_Account'] = (string)$From_Account;
        }

        return $this->http_request($url, $data_arr);
    }

    /**
     * ----------------------------------------------------------
     * 群组相关
     * ----------------------------------------------------------
     */

    /**
     * 获取 App 中的所有群组
     * @param $Limit
     * @param $Next
     * @param string $GroupType
     * @return bool|string
     */
    public function getGroupList($Limit, $Next, $GroupType = ''){
        $url = $this->urlClass->getGroupListUrl();
        $data = [
            'Limit' => (int)$Limit,
            'Next' => $Next,
        ];
        if ($GroupType != ''){
            $data['GroupType'] = $GroupType;
        }
        return $this->http_request($url,$data);
    }

    /**
     * 获取群详细资料
     * @param array $GroupIdList ['@TGS#1NVTZEAE4','@TGS#1CXTZEAET']
     * @return array|bool|string
     *
     */
    public function getGroupInfo(array $GroupIdList)
    {
        $url = $this->urlClass->getGroupInfoUrl();
        $data = [
            'GroupIdList' => $GroupIdList,
        ];

        return $this->http_request($url,$data);
    }

    /**
     * 判断群组是否存在
     * @param $grout_id
     * @return bool
     */
    public function isGroup($grout_id)
    {
        $res = $this->getGroupInfo([$grout_id]);
        if (isset($res['GroupInfo'][0]['ErrorCode']) && $res['GroupInfo'][0]['ErrorCode'] == 0){
            return true;
        }
        return false;
    }

    /**
     * 创建群聊
     * @param $Owner_Account string 群主的 UserId
     * @param string $Name 群名称（必填）
     * @param string $Type 群组类型：
     *                              Public（陌生人社交群），
     *                              Private（即 Work，好友工作群），
     *                              ChatRoom（即 Meeting，会议群），
     *                              AVChatRoom（直播群）
     *
     * @return array {
                        "ActionStatus": "OK",
                        "ErrorInfo": "",
                        "ErrorCode": 0,
                        "GroupId": "MyFirstGroup"
                     }
     */
    public function createGroup($Owner_Account,$Name,$Type='Public'):array
    {
        $url = $this->urlClass->crateGroupUrl();
        $data = [
            'Owner_Account' => (string)$Owner_Account,
            'Type' => (string)$Type,
            'Name'=> (string)$Name
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 增加群成员
     * @param $GroupId string 群id @TGS#2J4SZEAEL
     * @param $MemberList array [
     *                             ['Member_Account'=>3343],
     *                          ]
     *  一次最多添加300个成员
     * @param $Silence int 1给所有成员下发通知 0不发
     * @return array
     *
     * success array(4) {
                ["ActionStatus"]=> string(2) "OK"
                ["ErrorCode"]=> int(0)
                ["ErrorInfo"]=> string(0) ""
                ["MemberList"]=> array(1) {
                    [0]=>
                        array(2) {
                            ["Member_Account"]=> string(5) "34338"
                            ["Result"]=> int(2)
                        }
                    }
                }
     * fail
     */
    public function addGroupMember($GroupId,array $MemberList,int $Silence=0):array {
        $url = $this->urlClass->addGroupMemberUrl();
        $data = [
            'GroupId' => $GroupId,
            'MemberList' => $MemberList,
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 退出群组
     * @param string $GroupId
     * @param array $MemberToDel_Account ['user1','user2']
     * @return array
     */
    public function deleteGroupMember($GroupId,$MemberToDel_Account){
        $url = $this->urlClass->deleteGroupMemberUrl();
        $data = [
            'GroupId' => $GroupId,
            'MemberToDel_Account' => $MemberToDel_Account,
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 在群组中发送系统通知
     * @param $GroupId
     * @param $Content
     * @return array
     */
    public function sendGroupSystemNotification($GroupId,$Content):array {
        $url = $this->urlClass->sendGroupSystemNotificationUrl();
        $data = [
            'GroupId' => $GroupId,
            'Content' => $Content,
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 解散群组
     * @param string $GroupId
     * @return array
     */
    public function destroyGroup(string $GroupId):array {
        $url = $this->urlClass->destroyGroupUrl();
        $data = [
            'GroupId' => $GroupId,
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 群组中发送普通消息
     * @param string $GroupId
     * @param array  $MsgBody
     * @return array
     */
    public function sendGroupMsg(string $GroupId,$MsgBody):array {
        $rand = $this->rten();
        $url  = $this->urlClass->sendGroupMsgUrl();
        $data = [
            'GroupId' => $GroupId,
            'Random'  => $rand,
            'MsgBody' => [$MsgBody],
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 获取群成员详细资料
     * @param string $GroupId
     * @param int $Limit
     * @param int $Offset
     * @param array $MemberInfoFilter
     * @return bool|string
     */
    public function getGroupMemberInfo(string $GroupId,int $Limit,int $Offset,array $MemberInfoFilter=[])
    {
        $url = $this->urlClass->getGroupMemberInfoUrl();
        $data = [
            'GroupId' => $GroupId,
            'Limit'  => $Limit,  // 最多获取多少个成员的资料
            'Offset' => $Offset, // 从第多少个成员开始获取资料
        ];
        if (!empty($MemberInfoFilter)){
            $data['MemberInfoFilter'] = $MemberInfoFilter;
        }
        return $this->http_request($url,$data);
    }


    /**
     * ----------------------------------------------------------
     * 好友相关
     * ----------------------------------------------------------
     */

    /**
     * 拉取好友（好友列表）
     * @param $From_Account
     * @param int $StartIndex
     * @param int $StandardSequence
     * @param int $CustomSequence
     * @return array
     */
    public function friendGet($From_Account,$StartIndex=0,$StandardSequence=0,$CustomSequence=0):array {
        $url = $this->urlClass->friendGetUrl();
        $data = [
            'From_Account' =>$From_Account,
            'StartIndex'=>(int)$StartIndex,
            'StandardSequence'=>(int)$StandardSequence,
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 校验好友
     * @param $id
     * @param array $array
     * @return array
     */
    public function friendCheck($id,array $array):array {
        $url = $this->urlClass->friendCheckUrl();
        $data = [
            'From_Account' =>(string)$id,
            'To_Account'   =>$array,
            'CheckType'    =>'CheckResult_Type_Single',
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 添加好友
     * @param $From_Account
     * @param $To_Account
     * @param string $AddSource
     *        默认 AddSource_Type_Android 来自安卓
     *             AddSource_Type_Ios 来自安IOS
     * @return bool|mixed|string
     */
    public function friendAdd($From_Account,$To_Account,$AddSource="AddSource_Type_Android"){
        $url = $this->urlClass->friendAddUrl();
        $AddFriendItem = [];
        $AddFriendItem['To_Account'] = (string)$To_Account;
        $AddFriendItem['AddSource'] = $AddSource;
        $data = [
            'AddType' => 'Add_Type_Both',
            'From_Account' => (string)$From_Account,
            'AddFriendItem' => [$AddFriendItem],

        ];
        return $this->http_request($url,$data);
    }

    /**
     * 删除好友
     * @param $From_Account
     * @param $To_Account
     * @param string $AddSource
     * @return bool|mixed|string
     */
    public function friendDelete($From_Account,$To_Account,$DeleteType='Delete_Type_Both'){
        $url = $this->urlClass->friendDelete();
        $data = [
            'From_Account' => (string)$From_Account,
            'To_Account' => [$To_Account],
            'DeleteType' => $DeleteType
        ];
        return $this->http_request($url,$data);
    }

    /**
     * 清除sign缓存
     * @return mixed
     */
    public function cleanSign()
    {
        return DI()->redis->del($this->cacheSignKey);
    }

    /**
     * curl请求
     * @param $url
     * @param null $data
     * @param array $headers
     * @return bool|string
     */
    private function http_request(string $url, array $data):array
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        $res_arr = json_decode($output, true);
        if (!isset($res_arr['ErrorCode']) || $res_arr['ErrorCode'] != 0){
            DI()->logger->error('腾讯云IM错误', 'res:'.$output.' url:'.$url.' data:'.json_encode($data));
        }
        return (array)$res_arr;
    }

    /**
     * 获取随机数
     * @return string
     */
    private function rten(){
        return rand(1000000000000000,9999999999999999).rand(1000000000000000,9999999999999999);
    }
}