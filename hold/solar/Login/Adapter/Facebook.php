<?php
/**
 *
 * Login protocol based on having a facebook session
 *
 * @package Aura.Auth
 *
 * @author Richard Thomas <richard@phpjack.com>
 *
 */
class Auth_Login_Adapter_Facebook extends Solar_Auth_Login_Adapter
{
    /**
     *
     * Default configuration values.
     *
     * @config dependency facebook A dependency on a Facebook instance;
     *  default is a Solar_Registry entry named 'facebook'.
     *
     * @config string source_redirect Element key in the credential array source to indicate
     *   where to redirect on successful login, default 'redirect'.
     *
     * @config string fb_email what key this value can be found in the facebook return data
     *
     * @config string fb_username what key this value can be found in the facebook return data
     *
     * @config string fb_displayname what key this value can be found in the facebook return data
     *
     * @config string fb_id what key this value can be found in the facebook return data
     *
     * @var array
     *
     */
    protected $_Solar_Auth_Login_Adapter_Facebook = array(
        'facebook'          => 'facebook',
        'source_redirect'   => 'redirect',
        'fb_email'          => 'email',
        'fb_username'         => 'email',
        'fb_displayname'        => 'name',
        'fb_id'             => 'id',
    );

    /**
     *
     * A Facebook library instance.
     *
     * @var Facebook
     *
     */
    protected $_facebook;

    /**
     *
     * Set up the dependency to the Facebook object.
     *
     * @return null
     *
     */
    protected function _postConstruct()
    {
        parent::_postConstruct();

        $this->_facebook = Solar::dependency(
            'Facebook',
            $this->_config['facebook']
        );
    }

    /**
     *
     * Tells if the current page load appears to be the result of
     * an attempt to log in.
     *
     * @return bool
     *
     */
    public function isLoginRequest()
    {
        // check for a facebook session
        if ($this->_request->cookie('fbs_'.$this->_facebook->getAppId())) {
            return true;
        }
    }

    /**
     *
     * Verify we have a facebook session and load the credentials
     *
     * @return array List of authentication credentials
     *
     */
    public function getCredentials()
    {
        // We have a possible session lets get our user data
        if ($this->_facebook->getSession()) {
            try {
                $fb_results = $this->_facebook->api('/me');
                return array(
                    'id'        => $fb_results[$this->_config['fb_id']],  // username
                    'username'    => $fb_results[$this->_config['fb_username']],  // username
                    'email'     => $fb_results[$this->_config['fb_email']], // email
                    'displayname'   => $fb_results[$this->_config['fb_displayname']],  // display name
                    'verified'  => true,
                );
            } catch (FacebookApiException $e) {
                // Session is invalid, login failed
            }
        }
        return false;
    }

    /**
     *
     * The login was success, complete the protocol
     *
     * @return null
     *
     */
    public function postLoginSuccess()
    {
    }

    /**
     *
     * The login was a failure, complete the protocol
     *
     * @return null
     *
     */
    public function postLoginFailure()
    {
    }

    /**
     *
     * Looks at the value of the 'redirect' source key, and determines a
     * redirection url from it.
     *
     * If the 'redirect' key is empty or not present, will not redirect, and
     * processing will continue.
     *
     * @return string|null The url to redirect to or null if no redirect
     *
     */
    public function getLoginRedirect()
    {
        return $this->_request->post($this->_config['source_redirect']);
    }
}
