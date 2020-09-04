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
        $Collection = $Collections->get_one_by('collectionKey', $collection_key);

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

        $API      = new PerchAPI(1.0, 'pipit_importer');
        $Importer = $API->get('CollectionImporter');
        $Template = $API->get('Template');

        $Importer->set_collection($Collection->collectionKey());
        $Template->set('content/' . $Collection->collectionTemplate(), 'content');
        $Importer->set_template($Template);

        $data = $SubmittedForm->data;
        $items = [];
        if(isset($data['_id']) && !empty($data['_id'])) {
            $items = $Importer->find_items([
                '_id' => $data['_id'],
            ]);

            if(!count($items)) {
                PerchUtil::debug('Collection item not found', 'error');
                $SubmittedForm->throw_error('item_not_found');
                return false;
            }
        }


        $assets = $this->_handle_submitted_files($SubmittedForm, $Template);
        if($assets) {
            $data = array_merge($data, $assets);
        }

        $data = $this->_unset_empty_fields($SubmittedForm, $Template, $data);


        try {
            if(count($items)) {
                foreach($items as $item) {
                    $Importer->update_item($item['_id'], $data);
                }
            } else {
                $Importer->add_item($data);
            }

            return true;

        } catch(Exception $e) {
            die('Error: '.$e->getMessage());
        }
        
    }





    /**
     * 
     * @param PerchAPI_SubmittedForm $SubmittedForm 
     * 
     */
    public function process_delete_form_response($SubmittedForm) {
        $Collection = $this->_get_collection_from_form($SubmittedForm);
        if(!is_object($Collection)) return false;

        $API      = new PerchAPI(1.0, 'pipit_members');
        $Importer = $API->get('CollectionImporter');
        $Importer->set_collection($Collection->collectionKey());

        $data = $SubmittedForm->data;
        $items = [];
        if(isset($data['_id']) && !empty($data['_id'])) {
            $items = $Importer->find_items([
                '_id' => $data['_id'],
            ]);

            if(!count($items)) {
                PerchUtil::debug('Collection item not found', 'error');
                $SubmittedForm->throw_error('item_not_found');
                return false;
            }
        }



        try {
            foreach($items as $item) {
                return $Importer->delete_item($item['_id']);
            }
        } catch(Exception $e) {
            die('Error: '.$e->getMessage());
        }
    }





    public function _handle_submitted_files($SubmittedForm, $Template) {
        if(!defined('PIPIT_MEMBERS_TMP_PATH')) {
            PerchUtil::debug('PIPIT_MEMBERS_TMP_PATH is not defined. Cannot import files');
            return false;
        }

        $form_max_file_size = false;
        $FormTag = $SubmittedForm->get_form_attributes();
        if($FormTag->is_set('perch_max_file_size')) {
            $form_max_file_size = (int) $FormTag->perch_max_file_size();
        }

        $API  = new PerchAPI(1.0, 'pipit_members');
        // $Assets = new PerchAssets_Assets;
        $AssetImporter = $API->get('AssetImporter');

        // handle files
        $assets = $result = [];
        $file_types = ['image', 'file'];


        // PerchUtil::debug($SubmittedForm->files);
        foreach($SubmittedForm->files as $key => $file) {
            if(!isset($file['name']) || empty($file['name'])) continue;

            $Tag = $Template->find_tag($key);

            if($Tag && in_array($Tag->type, $file_types)) {
                $bucket = 'default';
                if($Tag->bucket) $bucket = $Tag->bucket;


                
                if($form_max_file_size && $file['size'] > $form_max_file_size) {
                    $SubmittedForm->throw_error('upload_max_size', $key);
                    break;
                }



                $new_filename = PerchUtil::file_path(PIPIT_MEMBERS_TMP_PATH . '/' . $file['name']);
                PerchUtil::move_uploaded_file($file['tmp_name'], $new_filename);

                try {
                    $assets[$key] = $AssetImporter->add_item([
                                        'type'      => $Tag->type,
                                        'bucket'    => $bucket,
                                        'path'      => $new_filename,
                                    ]);

                    unlink($new_filename);
                } catch (Exception $e) {
                    die('Error: '.$e->getMessage());
                }
            }
        }

        
        
        foreach($assets as $key => $asset) {
            $result[$key . '_assetID'] = $asset['id'];
            // $Asset = $Assets->find($asset['id']);
            // $result[$key] = $Asset->web_path();
            $result[$key] = '';
        }

        return $result;
    }





    /**
     * @param PerchAPI_SubmittedForm $SubmittedForm
     * @param PerchAPI_Template $collectionTemplate
     * @param array $data
     * 
     * @return array
     */
    private function _unset_empty_fields($SubmittedForm, $collectionTemplate, $data) {
        $API      = new PerchAPI(1.0, 'pipit_members');
        $Template = $API->get('Template');
        
        $template_file = PerchUtil::file_path(PERCH_PATH . $SubmittedForm->templatePath);
        if(!file_exists($template_file)) return $data;
        
        $Template->set_from_string(file_get_contents($template_file), 'forms');
        $formTagIDs = $Template->find_all_tag_ids('input');
        
        $out = [];
        foreach($formTagIDs as $id) {
            if(isset($data[$id])) continue;
            
            $Tag = $collectionTemplate->find_tag($id);
            if(!$Tag) {
                $out[$id] = false;
                continue;
            }

            // some field types may require a different value to be unset
            switch($Tag->type()) {
                case 'file':
                case 'image':
                    // ignore files
                break;


                default:
                $out[$id] = false;
            }
        }


        return array_merge($data, $out);
    }

}