<?php

namespace Session\Handlers\File;


class SessionHandler extends \SessionHandler
{
    private $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function read($id)
    {
        $data = parent::read($id);
        return ( ! $data) ? '' :  $this->decrypt($data, $this->key);
    }

    public function write($id, $data)
    {
        $data = $this->encrypt($data, $this->key);
        return parent::write($id, $data);
    }

    /**
     * decrypt AES 256
     *
     * @param string $edata
     * @param string $password
     * @return string data
     */
    private function decrypt(string $edata, string $password): string
    {
        $data = base64_decode($edata);
        $salt = substr($data, 0, 16);
        $ct = substr($data, 16);

        $rounds = 3; // depends on key length
        $data00 = $password.$salt;
        $hash = array();
        $hash[0] = hash('sha256', $data00, true);
        $result = $hash[0];
        for ($i = 1; $i < $rounds; $i++)
        {
            $hash[$i] = hash('sha256', $hash[$i - 1].$data00, true);
            $result .= $hash[$i];
        }
        $key = substr($result, 0, 32);
        $iv  = substr($result, 32,16);
        $decrypted = openssl_decrypt($ct, 'AES-256-CBC', $key, true, $iv);

        return ( ! $decrypted) ? '' : $decrypted;
    }

    /**
     * crypt AES 256
     *
     * @param string $data
     * @param string $password
     * @return string encrypted data
     */
    private function encrypt(string $data, string $password): string
    {
        // Set a random salt
        $salt = openssl_random_pseudo_bytes(16);
        $salted = '';
        $dx = '';
        // Salt the key(32) and iv(16) = 48
        while (strlen($salted) < 48)
        {
            $dx = hash('sha256', $dx.$password.$salt, true);
            $salted .= $dx;
        }

        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32,16);

        $encrypted_data = openssl_encrypt($data, 'AES-256-CBC', $key, true, $iv);
        return base64_encode($salt . $encrypted_data);
    }
}