<?php
namespace Custom\ProjectBundle\Security;
 
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder as BaseMessageDigestPasswordEncoder;
 
class MessageDigestPasswordEncoder extends BaseMessageDigestPasswordEncoder
{
    private $algorithm;
    private $encodeHashAsBase64;
 
    public function __construct($algorithm = 'sha512', $encodeHashAsBase64 = true, $iterations = 5000)
    {
        $this->algorithm = $algorithm;
        $this->encodeHashAsBase64 = $encodeHashAsBase64;
        $this->iterations = $iterations;
    }
 
    protected function mergePasswordAndSalt($password, $salt)
    {
        if (empty($salt)) {
            return $password;
        }
        return $salt.$password; // or do whatever you need with the password and salt
    }
 
    public function encodePassword($raw, $salt)
    {
        // this is the original code from the extended class, change it as needed
 
        if (!in_array($this->algorithm, hash_algos(), true)) {
            throw new \LogicException(sprintf('The algorithm "%s" is not supported.', $this->algorithm));
        }
 
        $salted = $this->mergePasswordAndSalt($raw, $salt);
        $digest = hash($this->algorithm, $salted, true);
 
        // "stretch" hash
        for ($i = 1; $i < $this->iterations; $i++) {
            $digest = hash($this->algorithm, $digest.$salted, true);
        }
 
        return $this->encodeHashAsBase64 ? base64_encode($digest) :  bin2hex($digest);
    }
}
