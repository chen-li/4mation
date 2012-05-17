<?php
App::uses('AppModel', 'Model');
/**
 * Product Model
 *
 */
class Product extends AppModel {
    
    /**
     * find 20 random products
     * @return array 
     */
    public function getRandomProducts($limit = 20){
        $query = "SELECT * FROM `products`
                WHERE `products`.id >= (
                    SELECT floor( RAND() * (
                        (SELECT MAX(`products`.id) FROM `products`)
                        -
                        (SELECT MIN(`products`.id) FROM `products`)
                    ) 
                    + (SELECT MIN(`products`.id) FROM `products`))
                )
                LIMIT $limit";
        return $this->query($query); 
    }
}
