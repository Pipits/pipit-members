<?php

class PipitMembers {

    public $email_verified_tag = 'emailverified';



    /**
     * 
     */
    public function find_member($id_or_email, $return_array = false) {
        $API     = new PerchAPI(1.0, 'perch_members');
        $Members = new PerchMembers_Members($API);

        if(is_numeric($id_or_email)) {
            $Member = $Members->find($id_or_email);
        } else {
            // assume email
            $members = $Members->get_by('memberEmail', $id_or_email);
            if(isset($members[0])) $Member = $members[0];
        }

        if($return_array) {
            $data = $Member->to_array();
            unset($data['password'], $data['memberPassword']);
            return $data;
        }
        return $Member;
    }
    




    /**
     * 
     */
    public function add_tag($tag, $id_or_email, $expiry_date=false) {
        $API  = new PerchAPI(1.0, 'perch_members');
        $Member = $this->find_member($id_or_email);
        
        if(!is_object($Member)) {
            PerchUtil::debug('Member not found.', 'error');
            return false;
        }

        $Tags = new PerchMembers_Tags($API);
        $Tag  = $Tags->find_or_create($tag);
        if (is_object($Tag)) {
            $Tag->add_to_member($Member->id(), $expiry_date);
            return true;
        }


        PerchUtil::debug('Could not add tag to member.', 'error');
        return false;
    }





    /**
     * 
     */
    public function remove_tag($tag, $id_or_email) {
        $API  = new PerchAPI(1.0, 'perch_members');
        $Member = $this->find_member($id_or_email);

        if(!is_object($Member)) {
            PerchUtil::debug('Member not found.', 'error');
            return false;
        }

        $Tags = new PerchMembers_Tags($API);
        $Tag  = $Tags->find_by_tag($tag);
        if (is_object($Tag)) {
            $Tag->remove_from_member($Member->id());
            return true;
        }


        PerchUtil::debug('Could not remove tag from member.', 'error');
        return false;
    }





    /**
     * 
     */
    public function has_tag($tag, $id) {
        $Member = $this->find_member($id);
        
        if(!is_object($Member)) {
            PerchUtil::debug('Member not found.', 'error');
            return false;
        }

        $API = new PerchAPI(1.0, 'pipit_membres');
        $DB = $API->get('DB');

        $member_tags_table = PERCH_DB_PREFIX . 'members_member_tags';
        $tags_table = PERCH_DB_PREFIX . 'members_tags';

        $row = $DB->get_row(
            "SELECT * FROM $member_tags_table 
            WHERE memberID=$id 
            AND tagID=(SELECT tagID FROM $tags_table  WHERE tag=". $DB->pdb($tag) . " LIMIT 1)"
        );

        
        return $row ? true : false;
    }



    
    /**
     * 
     */
    public function email_verification_url_for_member($memberID) {
        $API  = new PerchAPI(1.0, 'perch_members');
        $Members = new PerchMembers_Members($API);
        $Member = $Members->find($memberID);

        if(!is_object($Member)) {
            PerchUtil::debug('Member not found.', 'error');
            return false;
        }

        $token = $this->generate_token($Member);

        // email URL to member
        $memberEmail = $Member->memberEmail();
        $data = $Member->to_array();
        if(isset($data['password'])) unset($data['password']);
        
        $data['verify_token'] = $token;
        if(defined('SITE_URL')) $data['site_url'] = SITE_URL;
        if(defined('SITE_NAME')) $data['site_name'] = SITE_NAME;
        



        $Email = $API->get('Email');

        $sender_name = PERCH_EMAIL_FROM_NAME;
        $sender_email = PERCH_EMAIL_FROM;

        $Email->senderName($sender_name);
        $Email->senderEmail($sender_email);
        // $Email->replyToEmail($sender_email);
        $Email->recipientEmail($memberEmail);
        $Email->subject('Email Verification');

        $Email->set_template('members/emails/verify.html');
        $Email->template_method('perch');
        
        $Email->set_bulk($data);
        $Email->send();
    }





    /**
     * 
     */
    public function match_email_verification_loggedin($token, $memberID) {
        $API  = new PerchAPI(1.0, 'perch_members');
        $Members = new PerchMembers_Members($API);
        $Member = $Members->find($memberID);

        if(!is_object($Member)) {
            PerchUtil::debug('Member not found.', 'error');
            return false;
        }

        
        $parts = $this->decode_token($token);
        
        if($parts['memberID'] == $Member->id()
            && $parts['memberEmail'] == $Member->memberEmail() 
            && $parts['memberCreated'] == $Member->memberCreated() ) {


            // add tag
            $this->add_tag($this->email_verified_tag, $memberID);
            return true;
        } 
        
        
        
        return false;
    }






    /**
     * 
     */
    public function match_email_verification_not_loggedin($token) {
        $API  = new PerchAPI(1.0, 'perch_members');
        $Members = new PerchMembers_Members($API);

        $parts = $this->decode_token($token);
        $Member = $Members->find($parts['memberID']);

        if(!is_object($Member)) {
            PerchUtil::debug('Member not found.', 'error');
            return false;
        }


        if($parts['memberID'] == $Member->id()
            && $parts['memberEmail'] == $Member->memberEmail() 
            && $parts['memberCreated'] == $Member->memberCreated() ) {

            // add tag
            $this->add_tag($this->email_verified_tag, $Member->id());
            return true;
        } 
        
        
        
        return false;
    }






    /**
     * 
     */
    public function get_id_from_email_verification($token) {
        $API  = new PerchAPI(1.0, 'perch_members');
        $Members = new PerchMembers_Members($API);

        $parts = $this->decode_token($token);
        $Member = $Members->find($parts['memberID']);

        if(!is_object($Member)) {
            PerchUtil::debug('Member not found.', 'error');
            return false;
        }

        return $Member->id();
    }




    
    /**
     * generate token
     */
    private function generate_token($Member) {
        if(!is_object($Member)) return false;

        $token = base64_encode($Member->id().'^'.$Member->memberEmail().'^'.$Member->memberCreated());

        return $token;
    }


    
    
    /**
     * 
     */
    private function decode_token($token) {
        $token = base64_decode($token);
        $parts = explode('^', $token);

        $result = [
            'memberID' => '',
            'memberEmail' => '',
            'memberCreated' => '',
        ];

        if(is_array($parts) && count($parts) > 0) {
            $result['memberID'] = (isset($parts[0]) ? $parts[0] : false);
            $result['memberEmail'] = (isset($parts[1]) ? $parts[1] : false);
            $result['memberCreated'] = (isset($parts[2]) ? $parts[2] : false);
        }

        return $result;
    }
}