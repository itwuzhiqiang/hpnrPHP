<?php

class Controller_Abstract {
    public static $passportObject;
    public $passport;
    public $police;
    public $userId;

    /**
     * 通行证类
     * @return PadBasePassport
     */
    static public function getPassport() {
        if (!self::$passportObject) {
            self::$passportObject = new PadBasePassport(
                array(
                    'cookie_name' => 'police',
                    'identity_cookie_name' => 'policeData',
                    'expire_time' => 0,
                    'des_key' => '3d69deab-6661-4f53-8f0d-61e23aa4fd6fs'
                ));
        }
        return self::$passportObject;
    }

    public function __construct(PadMvcRequest $request, PadMvcResponse $response) {
        $data = array();
        $passport = self::getPassport();
        $this->passport = $passport;
        $cookieKey = $passport->getCookieKey();

//		$cookieUserId = $passport->getCookie('userId');
        $userId = $request->param('user_id');
//		$passport->setCookie('userId', $userId);
//		$userId = $passport->getCookie('userId');

        if ($userId || $cookieKey) {
            $url = config('domain.rpapi') . '/external/v2/user/police?user_id=' . $userId;

            if ($userId) {
                $this->userId = $userId;
                $pcurl = new PadLib_Pcurl();
                list($content, $info) = $pcurl->get($url, array(
                    'post' => array()
                ));
                $arr = json_decode($content, true);
                if ($arr) {
                    $code = $arr['code'];
                    if ($code != 1) {
                        echo "<script>alert('code!=1')</script>";
                        exit;
                    } else {
                        $data = $arr['data'];
                        Controller_Abstract::getPassport()->setLogin(json_encode($data));
                    }
                } else {
                    //事故接口经常挂
                    echo "<script>alert('连接失败请联系管理员')</script>";
                    exit;
                }
            } else if ($cookieKey) {
                $data = json_decode($cookieKey, true);
            }
        } else {
            echo "<script>alert('参数错误')</script>";
            exit;
        }

        $this->police = $data;
    }

}


