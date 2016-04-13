<?php
/**
 * File: CheckCode.php
 *
 * <pre> 描述:验证码类 </pre>
 *
 * @category   PHP
 * @package    File
 * @subpackage Include
 * @author     yanglibin@peopleyunqing.com
 * @copyright  2015 peopleyunqing, Inc.
 * @license    BSD Licence
 * @link       http://example.com
 */
namespace app\mvc\_base\srv;

class CheckCode
{
    /**
     * 验证码字符串
     * @var string
     */
    private $_m_codeTable;

    /**
     * 验证码cookie名称
     * @var string
     */
    private $_m_cookieName;

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 初始化中文数组
        $this->_m_codeTable = "0123456789abcdefghijklmnopqrstuvwxyz";
        $this->m_codeLens = strlen($this->_m_codeTable);
        $this->_m_cookieName = "_CMSPEOPLEIMAGECODE_";
        $this->m_fonts = array(
            'simfang.ttf', 'simhei.ttf', 'simkai.ttf', 'simsun.ttc', 'SimSun18030.ttc'
        );
    }

    /**
     * 校验函数
     * @param string $str 用户输入的字符串
     * @return boolean
     */
    public function check($str)
    {
        // 1.得到cookie值
        if (!isset($_COOKIE[$this->_m_cookieName]))
            return false;
        $cookiestr = $_COOKIE[$this->_m_cookieName];
        $cookiestr = base64_decode($cookiestr);
        $arr = unserialize($cookiestr);
        if (!isset($arr['data']) || !isset($arr['check']))
            return false;

        $sum = 0;
        $mystr = "";
        if (is_array($arr['data']) && count($arr['data']) > 0) {
            foreach ($arr['data'] as $key => $num) {
                $sum += intval($num);
                $s1 = $this->_m_codeTable[$num];
                $s2 = $this->_m_codeTable[$num + 1];
                $mystr .= "$s1$s2";
            }
        }
        if ($sum <= 0)
            return false;

        if (md5($sum) != $arr['check'])
            return false;

        if ($mystr != $str)
            return false;

        return true;
    }

    /**
     * 生成验证码
     * @param  integer $width 宽度（单位像素）
     * @param  integer $height 高度（单位像素）
     * @param  integer $num 数字（单位像素）
     * @param  integer $interval 间隔（单位像素）
     * @param  integer $left 左侧位移（单位像素）
     * @return image(png)
     */
    public function createCodeImage($width, $height, $num = 2, $interval = 20, $left = 5)
    {
        // 1.随机得num个数字，查询中文，生成图片
        $num_array = false;
        $str_array = false;
        $num = intval($num);
        if ($num <= 0)
            return false;
        $sum = 0;
        for ($i = 0; $i < $num; $i++) {
            $num1 = rand(0, $this->m_codeLens - 1);
            if ($num1 % 2 != 0) {
                $num1 = $num1 + 1;
                if ($num1 >= ($this->m_codeLens - 1)) {
                    $num1 = $num1 - 2;
                }
            }

            $num_array[$i] = $num1;
            $str_array[$i] = $this->_m_codeTable[$num1] . "" . $this->_m_codeTable[$num1 + 1];
            $sum += $num1;
        }

        $fontsuffix = rand(0, count($this->m_fonts) - 1);
        $fontface = dirname(dirname(dirname(__DIR__))) . "/lib/font/" . $this->m_fonts[$fontsuffix];
        $im = imagecreatetruecolor($width, $height);
        $w = $width;
        $h = $height;
        $bkcolor = imagecolorallocate($im, 250, 250, 250);
        imagefill($im, 0, 0, $bkcolor);

        /**
         * *添加干扰**
         */
        for ($i = 0; $i < 15; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagearc($im, mt_rand(-10, $w), mt_rand(-10, $h), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $fontcolor);
        }
        for ($i = 0; $i < 255; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, $w), mt_rand(0, $h), $fontcolor);
        }
        /**
         * *********内容********
         */
        for ($i = 0; $i < $num; $i++) {
            // 这样保证随机出来的颜色较深。
            $fontcolor = imagecolorallocate($im, mt_rand(0, 10), mt_rand(0, 10), mt_rand(0, 10));
            $codex = @iconv("GBK//IGNORE", "UTF-8//IGNORE", $str_array[$i]);
            imagettftext($im, 18, 0, $interval * $i + $left, 25, $fontcolor, $fontface, $codex);
        }
        // 2.种置加密cookie
        $cookiestr = serialize(array('data' => $num_array, 'check' => md5($sum)));
        $cookiestr = base64_encode($cookiestr);
        $_COOKIE[$this->_m_cookieName] = $cookiestr;
        //setcookie($this->_m_cookieName, $cookiestr,0, "/", "cms.dev.com");
        setcookie($this->_m_cookieName, $cookiestr, 0);
        // 3.输出图片
        header("Content-type: image/png");
        imagepng($im);
        imagedestroy($im);
    }
}

/* End of file CheckCode.php */
