SELECT * FROM `products`
WHERE `products`.id >= (
    SELECT floor( RAND() * (
	(SELECT MAX(`products`.id) FROM `products`)
	-
	(SELECT MIN(`products`.id) FROM `products`)
    ) 
    + (SELECT MIN(`products`.id) FROM `products`))
)
LIMIT 20;