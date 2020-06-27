<?php
    include(__DIR__ . '/lib/PipitMembers.class.php');



    /**
     * Find a member by ID or email address
     * @param string $id_or_email
     * @param boolean return_array
     * 
     * @return object|array
     */
    function pipit_members_find($id_or_email, $return_array=true) {
        $PipitMembers = new PipitMembers;
        return $PipitMembers->find_member($id_or_email, $return_array);    
    }




    /**
     * Add a tag to a member from the Perch Membres app
     * @param string $tag
     * @param int|string $id_or_email
     * @param string|boolean $expiry_date
     */
    function pipit_members_add_tag($tag, $id_or_email, $expiry_date=false) {
        $PipitMembers = new PipitMembers;
        return $PipitMembers->add_tag($tag, $id_or_email, $expiry_date);
    }




    /**
     * Remove a tag from a member from the Perch Membres app
     * 
     * @param string $tag
     * @param int|string $id_or_email
     */
    function pipit_members_remove_tag($tag, $id_or_email) {
        $PipitMembers = new PipitMembers;
        return $PipitMembers->remove_tag($tag, $id_or_email);
    }




    /**
     * Check whether a member has a tag
     * 
     * @param string $tag
     * @param int|string $id_or_email
     * @return boolean
     */
    function pipit_member_has_tag($tag, $id_or_email=0) {
        $PipitMembers = new PipitMembers;

        if(!$id_or_email && perch_member_logged_in()) {
            $id_or_email = perch_member_get('id');
        }


        return $PipitMembers->has_tag($tag, $id_or_email);
    }






    /**
     * Send email verification
     * 
     * @param int $memberID
     */
    function pipit_members_send_email_verification($memberID = 0) {
        if(!$memberID && !perch_member_logged_in()) return false;
        if(!$memberID) $memberID = perch_member_get('id');


        $PipitMembers = new PipitMembers;
        $PipitMembers->email_verification_url_for_member($memberID);
        $PipitMembers->add_tag('email-verify-created', $memberID, '+10 minutes');
    }






    /**
     * Verify email token
     * @param string $token
     * @param string $add_tag
     * @param string $remove_tag
     */
    function pipit_members_verify_email_token($token, $add_tag='', $remove_tag='') {
        $PipitMembers = new PipitMembers;
        $result = false;


        if(perch_member_logged_in()) {



            $result = $PipitMembers->match_email_verification_loggedin($token, perch_member_get('id'));

            if($add_tag) {
                $add_tags = explode(',', $add_tag);
                if(count($add_tags)) {
                    foreach($add_tags as $tag) {
                        perch_member_add_tag($tag);
                    }
                }
            } 


            if($remove_tag) {
                $remove_tags = explode(',', $remove_tag);
                if(count($remove_tags)) {
                    foreach($remove_tags as $tag) {
                        perch_member_remove_tag($tag);
                    }
                }
            }



        } else {



            $result = $PipitMembers->match_email_verification_not_loggedin($token);
            $memberID = $PipitMembers->get_id_from_email_verification($token); 

            if($memberID) {
                if($add_tag) {
                    $add_tags = explode(',', $add_tag);
                    if(count($add_tags)) {
                        foreach($add_tags as $tag) {
                            pipit_members_add_tag($tag, $memberID);
                        }
                    }
                }

                
                if($remove_tag) {
                    $remove_tags = explode(',', $add_tag);
                    if(count($remove_tags)) {
                        foreach($remove_tags as $tag) {
                            pipit_members_remove_tag($tag, $memberID);
                        }
                    }
                }
            } 



        }



        return $result;
    }









    
    /**
     * 
     */
    function pipit_members_form_handler($SubmittedForm) {
        if($SubmittedForm->validate()) {

            switch($SubmittedForm->formID) {
                case 'member_auth':
                    if(!perch_member_logged_in()) {
                        $SubmittedForm->throw_error('unauthorized', 'all');
                    }

                    if(!isset($SubmittedForm->data['token']) || $SubmittedForm->data['token'] != perch_member_get('token')) {
                        $SubmittedForm->throw_error('unauthorized', 'token');
                    }
                break;



                case 'logout':
                    if(!perch_member_logged_in()) continue;

                    if(!isset($SubmittedForm->data['token']) || $SubmittedForm->data['token'] != perch_member_get('token')) {
                        $SubmittedForm->throw_error('unauthorized', 'token');
                    } else {
                        perch_member_log_out();
                    }
                break;



                case 'send_verify_email':
                    if(!isset($SubmittedForm->data['token']) || $SubmittedForm->data['token'] != perch_member_get('token')) {
                        $SubmittedForm->throw_error('unauthorized', 'token');
                    } else {

                        $expiry_tag_diff = pipit_members_tag_expiry('email-verify-created', true);

                        
                        if(pipit_member_has_tag('email-verify-created') && $expiry_tag_diff < 0) {
                            PerchUtil::debug('Verification email was sent less than 10 minutes ago',  'notice');
                            $redirect = isset($SubmittedForm->form_attributes['fail']) ? $SubmittedForm->form_attributes['fail'] : '';
                        } else {
                            pipit_members_email_verification();
                            $redirect = isset($SubmittedForm->form_attributes['r']) ? $SubmittedForm->form_attributes['r'] : '';
                        }

                        if($redirect) PerchSystem::redirect($redirect);
                    }
                break;



                case 'remove_tag':
                    if(!perch_member_logged_in()) continue;

                    if(!isset($SubmittedForm->data['token']) || $SubmittedForm->data['token'] != perch_member_get('token')) {
                        $SubmittedForm->throw_error('unauthorized', 'token');
                    } else {
                        $tag_attr =  isset($SubmittedForm->form_attributes['tag']) ? $SubmittedForm->form_attributes['tag'] : '';
                        $tags = explode(',', $tag_attr);
                        foreach($tags as $tag) {
                            perch_member_remove_tag($tag);
                        }
                    }
                break;



                case 'add_tag':
                    if(!perch_member_logged_in()) continue;

                    if(!isset($SubmittedForm->data['token']) || $SubmittedForm->data['token'] != perch_member_get('token')) {
                        $SubmittedForm->throw_error('unauthorized', 'token');
                    } else {
                        $tag_attr =  isset($SubmittedForm->form_attributes['tag']) ? $SubmittedForm->form_attributes['tag'] : '';
                        $tags = explode(',', $tag_attr);
                        foreach($tags as $tag) {
                            perch_member_add_tag($tag);
                        }

                        $redirect = isset($SubmittedForm->form_attributes['r']) ? $SubmittedForm->form_attributes['r'] : '';
                        if($redirect) PerchSystem::redirect($redirect);
                    }
                break;
            }




            // get form attributes
            $attrs = $apps = [];
            $Tag = $SubmittedForm->get_form_attributes();

            if (is_object($Tag) && isset($Tag->attributes)) {
                $attrs = $Tag->attributes;
            }

            // redispatch to other apps?
            if(isset($attrs['redispatch']) && $attrs['redispatch'] != '') {
                $apps = explode(' ', trim($attrs['redispatch'])); 
            }

            if(count($apps)) {
                foreach($apps as $app) {
                    PerchUtil::mark($app);
                    if (function_exists($app.'_form_handler') ) {
                        $SubmittedForm->redispatch($app);
                    }
                }
            }



            // redirect?
            $redirect = isset($SubmittedForm->form_attributes['r']) ? $SubmittedForm->form_attributes['r'] : '';
            if($redirect) PerchSystem::redirect($redirect);
        }
    }






    /**
     * 
     */
    function pipit_members_is_authorized_submission($SubmittedForm) {
        if(isset($SubmittedForm->data['token']) && $SubmittedForm->data['token'] == perch_member_get('token')) {
            return true;
        }
        
        $SubmittedForm->throw_error('unauthorized', 'token');
        return false;
    }





    /**
     * 
     */
    function pipit_members_tag_expiry($tag, $return_diff=false) {
        $API = new PerchAPI(1.0, 'pipit_twilio');
        $DB = $API->get('DB');
        
        $member_tags_table = PERCH_DB_PREFIX . 'members_member_tags';
        $tags_table = PERCH_DB_PREFIX . 'members_tags';
        $memberID = perch_member_get('id');
        

        $expiry_tag = $DB->get_value("SELECT tagExpires FROM $member_tags_table WHERE memberID=$memberID AND tagID IN (SELECT tagID from $tags_table WHERE tag=". $DB->pdb($tag) .") LIMIT 1");

        if($return_diff && $expiry_tag) {
            $new_time = date('Y-m-d H:i:s');
            return (strtotime($new_time) - strtotime($expiry_tag)) / 60;
        }

        return $expiry_tag;
    }