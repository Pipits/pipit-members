<?php

class PipitMembers_Collection {

    /**
     * 
     * @param PerchAPI_SubmittedForm $SubmittedForm 
     * @return false|void 
     */
    private function _get_collection_from_form($SubmittedForm) {
        $collection_key = isset($SubmittedForm->form_attributes['collection']) ? $SubmittedForm->form_attributes['collection'] : '';

        if(!$collection_key) {
            PerchUtil::debug('The collection attribute is not specified', 'notice');
            return false;
        }

        $Collections = new PerchContent_Collections;
        $Collection = $Collections->get_by('collectionKey', $collection_key);

        if(!is_object($Collection)) {
            PerchUtil::debug("The collection $collection_key does not exist", 'notice');
            return false;
        }

        
        return $Collection;
    }




    /**
     * 
     * @param PerchAPI_SubmittedForm $SubmittedForm 
     * @return void 
     */
    public function process_form_response($SubmittedForm) {
        $Collection = $this->_get_collection_from_form($SubmittedForm);
        if(!is_object($Collection)) return false;

        
    }



    /**
     * 
     * @param PerchAPI_SubmittedForm $SubmittedForm 
     * @return void 
     */
    public function process_delete_form_response($SubmittedForm) {
        $Collection = $this->_get_collection_from_form($SubmittedForm);
        if(!is_object($Collection)) return false;

    }

}