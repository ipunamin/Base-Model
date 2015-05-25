<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    protected $table = NULL;
    protected $collection = array();
    protected $primary_key = 'id';
    protected $fillable = array();
    protected $protected_key = array('created_at', 'updated_at');
    protected $error = NULL;
    protected $array_supported_field = array();  

    function __construct()
    {
        parent::__construct();
        $this->load->helper('date_helper');
    }

    /*
    | -------------------------------------------------------------------------
    | ORM OPERATION
    | -------------------------------------------------------------------------
    */ 

    public function get_id()
    {
        return $this->collection[$this->primary_key];        
    }

    public function find($val = NULL, $key = NULL)
    {   
        if($key === NULL) {
            $key = $this->primary_key;     
        }
        
        if(in_array($key, $this->array_supported_field)) {                    
            $val = ';'.$val.';';
            return $this->like($key, $val)->limit(1)->fetch(TRUE);       
        } else {
            return $this->where($key, $val)->limit(1)->fetch(TRUE);
        }
    }

    public function find_or_fail($val = NULL, $key = NULL)
    {                               
        if($key === NULL) {
            $key = $this->primary_key;     
        }
        
        $result = $this->where($key, $val)->limit(1)->fetch(TRUE);

        if($result === NULL) {
            show_404();
        }

        return $result;
    }

    public function create($input)
    {   
        $instance = new $this;
        return $instance->fill($input)->save();
    }

    public function save()
    {
        $primary_key_value = $this->get($this->primary_key);

        $data = $this->get_collection_values();
        
        if(isset($primary_key_value) && $primary_key_value != '')
        {
            $data['updated_at'] = mdate('%Y-%m-%d %H:%i:%s', now());

            $this->db->where($this->primary_key, $this->get($this->primary_key));
            if(!$this->db->update($this->table, $data)) {
                $this->error = $this->db->error();
                $this->session->set_flashdata('error', 'There was a problem in system. Please try again.');
                throw new Exception($this->error['message'], $this->error['code']);   
            }
            
            return $this;    
            
        }
        else
        {
            $data['created_at'] = mdate('%Y-%m-%d %H:%i:%s', now());
            $data['updated_at'] = mdate('%Y-%m-%d %H:%i:%s', now());

            if(!$this->db->insert($this->table, $data)) {
                $this->error = $this->db->error();
                $this->session->set_flashdata('error', 'There was a problem in system. Please try again.');
                throw new Exception($this->error['message'], $this->error['code']);   
            }
            
            $id = $this->db->insert_id();
            $this->collection[$this->primary_key] = $id;
            return $this;
        }

    }

    /*
    | -------------------------------------------------------------------------
    | DB FUNCTIONS
    | -------------------------------------------------------------------------
    */  

    function count()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }
    
    function order_by($field, $direction = NULL)
    {
        $this->db->order_by($field, $direction);
        return $this;      
    }

    function like($field, $match = NULL, $side = NULL)
    {
        $this->db->like($field, $match, $side);  
        return $this;      
    }
    
    function or_like($field, $match = NULL, $side = NULL)
    {
        $this->db->or_like($field, $match, $side);  
        return $this;      
    }
    
    function where($field, $value = NULL)
    {
        $this->db->where($field, $value);  
        return $this;      
    }
    
    function where_in($field, $value = NULL)
    {
        $this->db->where_in($field, $value);  
        return $this;      
    }
    
    function limit($limit, $per_page = NULL)
    {
        $this->db->limit($limit, $per_page);
        return $this;
    }

    function fetch($first = FALSE)
    {
        $result = $this->db->get($this->table);
        $result = $this->build($result);

        if($result === NULL) {
            if($first === TRUE) {
                return $result;
            } else {
                return array();    
            }
        }

        if($first === TRUE) {
            return end($result);
        } else {
            return $result;    
        }
    }

    public function paginate($limit = 0)
    {
        $this->load->library('pagination');

        $per_page = (int)$this->input->get('per_page');

        if($per_page <= 0) {
            $per_page = 0;    
        }

        $this->db->limit($limit, $per_page);
        $collection = $this->fetch();

        $query = $this->db->last_query();
        
        $all_record_query = substr($query, 0, strpos($query, 'LIMIT'));

        /* PAGINATION */        
        $config['total_rows'] = $this->db->query($all_record_query)->num_rows();
        $config['per_page'] = $limit;
        $config['page_query_string'] = TRUE;
        $config['reuse_query_string'] = FALSE;
        $config['base_url'] = current_url();
        $config['full_tag_open'] = "<ul class='pagination'>";
        $config['full_tag_close'] ="</ul>";
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = "<li class='disabled'><li class='active'><a href='javascript:void(0)'>";
        $config['cur_tag_close'] = "<span class='sr-only'></span></a></li>";
        $config['next_tag_open'] = "<li>";
        $config['next_tagl_close'] = "</li>";
        $config['prev_tag_open'] = "<li>";
        $config['prev_tagl_close'] = "</li>";
        $config['first_tag_open'] = "<li>";
        $config['first_tagl_close'] = "</li>";
        $config['last_tag_open'] = "<li>";
        $config['last_tagl_close'] = "</li>";

        $this->pagination->initialize($config);

        $return['collection'] = $collection;
        $return['render_pagination'] = $this->pagination->create_links();

        return $return;
    }

    function auto_fill()
    {
        $fields = $this->db->list_fields($this->table); 

        foreach($this->protected_key as $key) {
            unset($fields[$key]);
        }
        unset($fields[$this->primary_key]);

        $this->fillable = $fields;   

        return $this;
    }        

    /*
    | -------------------------------------------------------------------------
    | COLLECTION BUILDER, GET AND SET
    | -------------------------------------------------------------------------
    */   

    public function get_collection_values()
    {
        $data = array();
        foreach($this->fillable as $key)
        {
            if(isset($this->collection[$key]))
            {
                $data[$key] = $this->collection[$key];
            }
        }
        return $data;
    }

    public function set($key, $value = NULL)
    {
        if($value === NULL) {
            $value = '';
        }

        if(!in_array($key, $this->fillable)) {
            return $this;
        }

        if(in_array($key, $this->array_supported_field)) {    
            $value = $this->array_to_comma($value);
        }
        
        if(method_exists($this, 'set_'.$key)) {
            $this->$func($value);
        } else {
            $this->collection[$key] = $value;
        }

        return $this;
    }    

    public function fill($data)
    {
        foreach($data as $key=>$value) {
            $this->set($key, $value);
        }

        return $this;
    }

    public function get($key, $avoid_recursive = FALSE)
    {
        $val = NULL;

        if(isset($this->collection[$key])) {
            if($avoid_recursive) {
                $val = $this->collection[$key];    
            } else {
                $func = 'get_'.$key;  

                if(method_exists($this, $func)) {
                    $val = $this->$func();
                } else {
                    $val = $this->collection[$key];    
                }
            }
        }
        
        //if($val !== NULL) {
            if(in_array($key, $this->array_supported_field)) {
                $val = $this->comma_to_array($val);
            }
        //}

        return $val;    
    }

    public function build($result)
    {
        if($result->num_rows()>0) {

            $datas = array();

            foreach($result->result() as $row) {  
                $class = new $this;
                foreach($row as $key=>$value) {
                    if(!in_array($key, $this->protected_key)) {
                        $class->collection[$key] = $value;
                    }
                }
                $datas[] = $class;
            }

            return $datas;

        } else {
            return NULL;
        }
    }
    
    public function get_data()
    {
        return $this->collection;
    }

    /*
    | -------------------------------------------------------------------------
    | COMMON FUNCTIONS
    | -------------------------------------------------------------------------
    */ 

    public function dropdown($field, $value = NULL, $multiselect = FALSE, $disabled = FALSE, $fancy = FALSE)
    {
        $output = '';
        
        $datas = $this->fetch();

        if($disabled) {
            $disabled = 'disabled';            
        }
        
        if($fancy) {
            $fancy = 'chosen-select';            
        }

        if($multiselect) {
            $output .= '<select data-placeholder="-- Select --" multiple class="form-control '.$fancy.'" id="'.$field.'" name="'.$field.'[]" '.$disabled.' >';
        } else {
            $output .= '<select data-placeholder="-- Select --" class="form-control '.$fancy.'" id="'.$field.'" name="'.$field.'" '.$disabled.' >';
            $output .= '<option value="">-- SELECT --</option>';
        }

        foreach($datas as $data)
        {
            if($value != '') {
                if(in_array($data->get('id'), (array)$value)) {
                    $output .= '<option value="'.$data->get('id').'" selected="selected">'.$data->get('title').'</option>';
                } else {
                    $output .= '<option value="'.$data->get('id').'">'.$data->get('title').'</option>';
                }
            } else {
                $output .= '<option value="'.$data->get('id').'">'.$data->get('title').'</option>';
            }
        }

        $output .= '</select>';

        return $output;
    }
    
    public function array_to_comma($tmp)
    {
        if($tmp == '') {
            return '';
        }
        
        $tmp = implode(';',$tmp);
        $tmp = ';'.$tmp.';';
        $tmp = str_replace(';;','',$tmp);
        return $tmp;
    }

    public function comma_to_array($tmp)
    {
        $tmp = trim($tmp,';');

        if($tmp == '') {
            return array();
        }

        $tmp = explode(';',$tmp);
        return $tmp;
    }

}