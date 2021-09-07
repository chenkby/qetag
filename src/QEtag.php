<?php

/**
 * @link https://github.com/chenkby/qetag
 */

namespace chenkby\qetag;

/**
 * QEtag
 * 七牛etag算法的php实现。
 * 本代码在七牛官方的php etag上添加了getEtag方法
 */
final class QEtag
{
    const BLOCK_SIZE = 4194304; //4*1024*1024 分块上传块大小，该参数为接口规格，不能修改

    private static function packArray($v, $a)
    {
        return call_user_func_array('pack', array_merge(array($v), (array)$a));
    }

    private static function blockCount($fsize)
    {
        return intval(($fsize + (self::BLOCK_SIZE - 1)) / self::BLOCK_SIZE);
    }

    private static function calcSha1($data)
    {
        $sha1Str = sha1($data, true);
        $err = error_get_last();
        if ($err !== null) {
            return array(null, $err);
        }
        $byteArray = unpack('C*', $sha1Str);
        return array($byteArray, null);
    }


    public static function sum($filename)
    {
        $fhandler = fopen($filename, 'r');
        $err = error_get_last();
        if ($err !== null) {
            return array(null, $err);
        }

        $fstat = fstat($fhandler);
        $fsize = $fstat['size'];
        if ((int)$fsize === 0) {
            fclose($fhandler);
            return array('Fto5o-5ea0sNMlW_75VgGJCv2AcJ', null);
        }
        $blockCnt = self::blockCount($fsize);
        $sha1Buf = array();

        if ($blockCnt <= 1) {
            array_push($sha1Buf, 0x16);
            $fdata = fread($fhandler, self::BLOCK_SIZE);
            if ($err !== null) {
                fclose($fhandler);
                return array(null, $err);
            }
            list($sha1Code,) = self::calcSha1($fdata);
            $sha1Buf = array_merge($sha1Buf, $sha1Code);
        } else {
            array_push($sha1Buf, 0x96);
            $sha1BlockBuf = array();
            for ($i = 0; $i < $blockCnt; $i++) {
                $fdata = fread($fhandler, self::BLOCK_SIZE);
                list($sha1Code, $err) = self::calcSha1($fdata);
                if ($err !== null) {
                    fclose($fhandler);
                    return array(null, $err);
                }
                $sha1BlockBuf = array_merge($sha1BlockBuf, $sha1Code);
            }
            $tmpData = self::packArray('C*', $sha1BlockBuf);
            list($sha1Final,) = self::calcSha1($tmpData);
            $sha1Buf = array_merge($sha1Buf, $sha1Final);
        }
        $etag = static::base64_urlSafeEncode(self::packArray('C*', $sha1Buf));
        return array($etag, null);
    }

    /**
     * 返回etag,为空表示发生了错误
     * @param $filename
     * @return string
     */
    public static function getEtag($filename)
    {
        error_clear_last();
        $result = static::sum($filename);
        if (!is_array($result) || empty($result[0])) {
            return '';
        }
        return $result[0];
    }

    /**
     * 对提供的数据进行urlsafe的base64编码。
     *
     * @param string $data 待编码的数据，一般为字符串
     *
     * @return string 编码后的字符串
     * @link http://developer.qiniu.com/docs/v6/api/overview/appendix.html#urlsafe-base64
     */
    private static function base64_urlSafeEncode($data)
    {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($data));
    }
}