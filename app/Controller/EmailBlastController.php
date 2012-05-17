<?php
App::uses('AppController', 'Controller');
App::uses('File', 'Utility');
/**
 * Gallery Controller
 *
 * @property Gallery $Gallery
 */
class EmailBlastController extends AppController {
    
    public $paginate;

    public $helpers = array('Paginator');
    
    /**
     * the file name of the email template stored on the server
     * @var string 
     */
    private $tmp_email_template = 'tmpEmail.txt';
    
    /**
     * the file name of the config file for the cron job settings
     * @var string 
     */
    private $tmp_current_subscribers = 'tmpSubscribers.ini';
    
    /**
     * the number of the products fetched each time
     * @var int 
     */
    public $product_num = 20;
    
    /**
     * since the `subscribers` table contains a huge number of records, it is better to setup a cron job and run this method every 3 mins. 
     * by default, this method sends email to 10 clients
     * the content of the generated random products is stored in the tmpEmail.txt temporarily.
     * the temporary info of the subscribers is stored in the tmpSubscribers.ini
     * when the method sends the email to all the activated subscribers, the 2 temp files will be deleted
     * @return boolean 
     */
    public function blast() {
        $this->autoRender = false;
        $start = 1;
        
        if(!file_exists($this->tmp_email_template)){
            $products = $this->getRandomProducts($this->product_num);//get 20 random products
            $this->saveProductsOnServer($products);
        }else{
            $content = file_get_contents($this->tmp_email_template);
        }
        
        if(!file_exists($this->tmp_current_subscribers)){
            $start = 1;
        }else{
            $ini = parse_ini_file($this->tmp_current_subscribers);//read the variables defined in the temporary ini file
            $start = $ini['start'] + 1;
            if($start > $ini['pageCount']){//the job of email blasting is completed
                //clear all the temp files
                unlink($this->tmp_email_template);
                unlink($this->tmp_current_subscribers);
                return true;
            }
        }
        

        $subscribers = $this->getActivatedSubscribers($start, 10);//set a crob job and send the email to 10 clients each time
        $this->saveSubscibersOnServer($start, $this->params['paging']['Subscriber']['pageCount']);
        
        foreach($subscribers as $subscriber){
            if($this->Subscriber->myValidate($subscriber)){//if invalid email address
                continue;
            }else{
                $to = $subscriber['Subscriber']['email'];
                $this->sendMail($to, 'noreply@4mation.com.au', 'Hot Products', $content);
            }
        }
        
        return true;
    }
    
    /**
     * find the random products
     * @param int $limit
     * @return array 
     */
    private function getRandomProducts($limit) {
        $this->loadModel('Product');
        $products = $this->Product->getRandomProducts($limit);
        return $products;
    }
    
    /**
     * find the 10 activated subscribers each time
     * 
     * @param int $page
     * @param int $limit
     * @return array 
     */
    private function getActivatedSubscribers($page=1, $limit=10) {
        $this->loadModel('Subscriber');
        $this->paginate['limit'] = $limit;
        $this->paginate['page'] = $page;
        
        $condition = array(
            'Subscriber.enabled > 0'
        );
        $subscribers = $this->paginate('Subscriber', $condition);
        return $subscribers;
    }
    
    /**
     * store all the subsciber info into a local ini file
     * @param array $products 
     */
    private function saveSubscibersOnServer($start, $page_count){        
        $str  = '[current_subscribers]' . "\r\n";
        $str .= 'start = ' . $start . "\r\n";
        $str .= 'pageCount = ' . $page_count . "\r\n";
        $this->createTemplate($this->tmp_current_subscribers, $str);
    }
    
    /**
     * store all the product info into a local file
     * @param array $products 
     */
    private function saveProductsOnServer($products){
        $file_path = '../View/Emails/text/products.ctp';
        if(file_exists($file_path))
            $content = file_get_contents($file_path);
        
        preg_match_all('/\[(Product_[A-Za-z]+)_([0-9]+)\]/', $content, $matches);
        
        foreach($matches[0] as $key => $match){
            $replace = $products[$matches[2][$key]]['products'][strtolower($matches[1][$key])];
            $replace = (isset($replace)) ? $replace : '';
            $content = str_replace($match, $replace, $content);
        }
        
        $this->createTemplate($this->tmp_email_template, $content);
    }
    
    /**
     * store the email content into a txt file
     * @param string $content 
     */
    private function createTemplate($file_name, $content){
        $handle = fopen($file_name, 'w');
        fwrite($handle, $content);
        fclose($handle);
    }
    
    /**
     * send the email
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $content 
     */
    private function sendMail($to, $from, $subject, $content){
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $sheaders = $headers.'From: 4mation '.' <' . $from . '>' . "\r\n";
        mail($to, $subject, $content, $sheaders);
    }
}