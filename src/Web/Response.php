<?php
/**
 * @author ueaner <ueaner@gmail.com>
 */
namespace Soli\Web;

use Soli\Di\ContainerAwareInterface;
use Soli\Di\ContainerAwareTrait;

/**
 * 响应
 *
 *<pre>
 * $response = new Response();
 * $response->setStatusCode(200);
 * $response->setContent($content);
 *
 * $cookie = [
 *     'name' => 'hello',
 *     'value' => 'hi cookie',
 *     'expire' => 60,
 * ];
 * $response->setCookie($cookie);
 *
 * $response->setHeader("Cache-Control: max-age=0");
 *
 * $response->send();
 *</pre>
 *
 * @codeCoverageIgnore
 */
class Response implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * 状态码
     *
     * @var int
     */
    protected $code = 200;

    /**
     * 状态描述
     *
     * @var int
     */
    protected $message;

    /**
     * 响应内容
     *
     * @var string|array
     */
    protected $content = null;

    /**
     * 响应的数据类型
     *
     * @var string
     */
    protected $contentType = null;

    /**
     * 响应头信息
     *
     * @var array
     */
    protected $headers = [];

    /**
     * 响应 cookie 信息
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Response constructor.
     *
     * @param string $content 响应内容
     * @param int $code 状态码
     * @param string $message 状态描述
     */
    public function __construct(string $content = null, int $code = 200, string $message = null)
    {
        $this->content = $content;
        $this->setStatusCode($code, $message);
    }

    /**
     * 设置响应状态
     *
     * @param int $code 状态码
     * @param string $message 状态描述
     *
     * @return $this
     */
    public function setStatusCode(int $code, string $message = null)
    {
        $this->code = $code;
        $this->message = $message;

        return $this;
    }

    /**
     * 获取响应类型
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * 设置响应类型
     *
     *<pre>
     * $response->setContentType('application/javascript');
     *</pre>
     *
     * @param string $contentType
     * @param string $charset
     *
     * @return $this
     */
    public function setContentType($contentType, $charset = 'UTF-8')
    {
        $this->contentType = $contentType;
        $this->headers['Content-type'] = "$contentType; charset=$charset";

        return $this;
    }

    /**
     * 获取响应内容
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * 设置响应内容
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent(string $content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * 获取响应的 cookies 信息
     *
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * 设置响应的 cookie 信息
     *
     * @param array $cookie 单个 cookie 信息
     *
     * @return $this
     */
    public function setCookie(array $cookie)
    {
        $default = [
            'name' => '__cookieDefault',
            'value' => '',
            'expire' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true
        ];

        $cookie = array_merge($default, $cookie);
        $this->cookies[$cookie['name']] = $cookie;

        return $this;
    }

    /**
     * 获取响应的头信息
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * 设置响应头信息
     *
     * @param string $header
     * @param string $value
     *
     * @return $this
     */
    public function setHeader($header, $value = null)
    {
        if (is_string($header)) {
            $this->headers[$header] = $value;
        }

        return $this;
    }

    /**
     * 发送响应数据
     *
     * @return $this
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendCookies();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // 重置响应信息，terminate 的时候有可能需要收集响应信息
        $this->reset();

        return $this;
    }

    /**
     * 发送响应内容
     *
     * @return $this
     */
    public function sendContent()
    {
        echo $this->content;

        return $this;
    }

    /**
     * 发送响应 cookie
     *
     * @return $this
     */
    public function sendCookies()
    {
        foreach ($this->cookies as $name => $c) {
            setcookie(
                $name,
                $c['value'], // encryptValue
                $c['expire'],
                $c['path'],
                $c['domain'],
                $c['secure'],
                $c['httpOnly']
            );
        }

        return $this;
    }

    /**
     * 发送响应头
     *
     * @return $this
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            return $this;
        }

        if (isset($this->headers['Location']) && $this->code === 200) {
            $this->setStatusCode(302);
        }

        // 发送状态码
        http_response_code($this->code);

        // 发送自定义响应头
        foreach ($this->headers as $header => $value) {
            if (empty($value)) {
                header($header, true);
            } else {
                header("$header: $value", true);
            }
        }

        return $this;
    }

    public function reset()
    {
        $this->code = 200;
        $this->message = null;
        $this->headers = [];
        $this->cookies = [];
        $this->content = null;
        $this->contentType = null;
    }
}
