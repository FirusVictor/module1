<?php
require_once "Base.php";

class product extends Base
{
    public $messages;

    public function __construct()
    {
        parent::__construct();
        if (count($this->url) === 1 && $this->method == "POST" && $this->CheckAuth())
            $this->Add_product();
        if (count($this->url) === 2 && $this->method == "POST" && $this->CheckAuth())
            $this->Edit_product();
        if (count($this->url) === 2 && $this->method == "DELETE" && $this->CheckAuth())
            $this->Del_product();
        if (count($this->url) === 1 && $this->method == "GET")
            $this->All_product();
        if (count($this->url) === 2 && $this->method == "GET")
            $this->Once_product();
        if (count($this->url) === 3 && $this->method == "POST" && $this->url[2] === "comments" && $this->CheckAuth())
            $this->Add_comment();
        if (count($this->url) === 4 && $this->method == "DELETE" && $this->url[2] === "comments" && $this->CheckAuth())
            $this->Del_comment();
        if (count($this->url) === 3 && $this->method == "GET" && $this->url[1] === "tag")
            $this->Search_product();

    }

    private function Add_product()
    {
        //заголовок
        $title = $_POST["title"];
        if (empty($title))
            $this->messages['title'] = "empty title";
        $result = $this->db->query("SELECT `id` FROM `products` WHERE `title` = '$title'");
        if ($result->num_rows > 0)
            $this->messages['title'] = "product already exist";

        //Производитель
        $manufacturer = $_POST["manufacturer"];
        if (empty($manufacturer))
            $this->messages['manufacturer'] = "empty manufacturer";

        //Текст
        $text = $_POST["text"];
        if (empty($text))
            $this->messages['text'] = "empty text";

        //Теги
        $tags = explode(",", $_POST['tags']);


        //изображение
        $image = $_FILES['image'];
        if ($image['size'] > 2 * 1024 * 1024)
            $this->messages["image_size"] = "size more then 2MB";
        if ($image['type'] !== "image/jpeg" && $image['type'] !== "image/png")
            $this->messages["image_format"] = "incorrect format";

        if (count($this->messages) === 0) {
            if (move_uploaded_file($image['tmp_name'], "product_images/" . $image['name'])) {
                $sql = "INSERT INTO `products` (`title`,`manufacturer`,`text`,`image`) VALUES (
'$title',
'$manufacturer',
'$text',
'" . $image['name'] . "'
)";
                if ($this->db->query($sql)) {
                    $product_id = $this->db->insert_id;
                    foreach ($tags as $index => $elem) {
                        $this->db->query("INSERT INTO `tags` (`tag`,`product`) VALUES ('$elem','$product_id')");
                    }
                    $this->result = array(
                        ("status code") => 201,
                        ("status text") => "Successful creation",
                        ("body") => array(
                            ("status") => true,
                            ("post_id") => $product_id
                        )
                    );
                    return;
                } else {
                    $this->messages['db'] = "error insert into DB";
                }
            } else {
                $this->messages["upload image"] = "error upload image";
            }
        }
        $this->result = array(
            ("status code") => 400,
            ("status text") => "Creating error",
            ("body") => array(
                ("status") => false,
                ("message") => $this->messages
            )
        );
    }

    private function Edit_product()
    {
        $current_product_id = $this->url[1];
        $result = $this->db->query("SELECT `id` FROM `products` WHERE `id` = '$current_product_id'");
        if ($result->num_rows === 0) {
            $this->result = array(
                ("status code") => 404,
                ("status text") => "Product not found",
                ("body") => array(
                    ("message") => "Product not found"
                )
            );
            return;
        }
        //заголовок
        $title = $_POST["title"];
        if (empty($title))
            $this->messages['title'] = "empty title";
        $result = $this->db->query("SELECT `id` FROM `products` WHERE `title` = '$title' AND `id` <> '$current_product_id'");
        if ($result->num_rows > 0)
            $this->messages['title'] = "product already exist";

        //Производитель
        $manufacturer = $_POST["manufacturer"];
        if (empty($manufacturer))
            $this->messages['manufacturer'] = "empty manufacturer";

        //Текст
        $text = $_POST["text"];
        if (empty($text))
            $this->messages['text'] = "empty text";

        //Теги
        $tags = explode(",", $_POST['tags']);


        //изображение
        $image = $_FILES['image'];
        if ($image['size'] > 2 * 1024 * 1024)
            $this->messages["image_size"] = "size more then 2MB";
        if ($image['type'] !== "image/jpeg" && $image['type'] !== "image/png")
            $this->messages["image_format"] = "incorrect format";

        if (count($this->messages) === 0) {
            if (move_uploaded_file($image['tmp_name'], "product_images/" . $image['name'])) {
                $sql = "UPDATE `products` SET 
`title` ='$title',
`manufacturer` = '$manufacturer',
`text`= '$text',
`image` = '" . $image['name'] . "'
WHERE `id` = '$current_product_id'";
                if ($this->db->query($sql)) {
                    $this->db->query("DELETE FROM `tags` WHERE `product` = '$current_product_id'");
                    foreach ($tags as $index => $elem) {
                        $this->db->query("INSERT INTO `tags` (`tag`,`product`) VALUES ('$elem','$current_product_id')");
                    }
                    $result = $this->db->query("SELECT * FROM `products` WHERE `id` = '$current_product_id'");
                    $product = $result->fetch_array();
                    $this->result = array(
                        ("status code") => 201,
                        ("status text") => "Successful creation",
                        ("body") => array(
                            ("status") => true,
                            ("post") => array(
                                ("title") => $product['title'],
                                ("datatime") => $product['date'],
                                ("manufacturer") => $product['manufacturer'],
                                ("text") => $product["text"],
                                ("tags") => $_POST["tags"],
                                ("image") => "/api/product_images/" . $product['image']
                            )
                        )
                    );
                    return;
                } else {
                    $this->messages['db'] = "error insert into DB";
                }
            } else {
                $this->messages["upload image"] = "error upload image";
            }
        }
        $this->result = array(
            ("status code") => 400,
            ("status text") => "Editing error",
            ("body") => array(
                ("status") => false,
                ("message") => $this->messages
            )
        );
    }

    private function Del_product()
    {
        $current_product_id = $this->url[1];
        $result = $this->db->query("SELECT `id` FROM `products` WHERE `id` = '$current_product_id'");
        if ($result->num_rows > 0) {
            if ($this->db->query("DELETE FROM `products` WHERE `id` = '$current_product_id'")) {
                $this->result = array(
                    ("status code") => 201,
                    ("status text") => "Successful delete",
                    ("body") => array(
                        ("status") => true
                    )
                );
            }
        } else {
            $this->result = array(
                ("status code") => 404,
                ("status text") => "Product not found",
                ("body") => array(
                    ("message") => "Product not found"
                )
            );
        }

    }

    private function All_product()
    {
        $result = $this->db->query("SELECT * FROM `products`");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array()) {
                $product_id = $row['id'];
                $result2 = $this->db->query("SELECT * FROM `tags` WHERE `product` = '$product_id'");
                $tags = [];
                while ($row2 = $result2->fetch_array()) {
                    $tags[] = $row2['tag'];
                }
                $body[] = array(
                    ("title") => $row['title'],
                    ("datatime") => $row['date'],
                    ("manufacturer") => $row['manufacturer'],
                    ("text") => $row['text'],
                    ("tags") => $tags,
                    ("image") => '/product_images/' . $row['image']
                );
            }
        }
        $this->result = array(
            ("status code") => 200,
            ("status text") => "List products",
            ("body") => $body
        );
    }

    private function Once_product()
    {
        $product_id = $this->url[1];
        $result = $this->db->query("SELECT * FROM `products` WHERE `id` = '$product_id'");
        if ($result->num_rows > 0) {
            $row = $result->fetch_array();

            $result2 = $this->db->query("SELECT * FROM `tags` WHERE `product` = '$product_id'");
            $tags = [];
            while ($row2 = $result2->fetch_array()) {
                $tags[] = $row2['tag'];
            }

            $result2 = $this->db->query("SELECT * FROM `comments` WHERE `product` = '$product_id'");
            $comments = [];
            while ($row2 = $result2->fetch_array()) {
                $comments[] = array(
                    ("comment_id") => $row2['id'],
                    ("datatime") => $row2['date'],
                    ("author") => $row2['author'],
                    ("comment") => $row2['comment']
                );
            }

            $body = array(
                ("title") => $row['title'],
                ("datatime") => $row['date'],
                ("manufacturer") => $row['manufacturer'],
                ("text") => $row['text'],
                ("tags") => $tags,
                ("image") => '/product_images/' . $row['image'],
                ("comments") => $comments
            );
            $this->result = array(
                ("status code") => 200,
                ("status text") => "View product",
                ("body") => $body
            );
        } else {
            $this->result = array(
                ("status code") => 404,
                ("status text") => "Product not found",
                ("body") => array(
                    ("message") => "Product not found"
                )
            );
        }

    }

    private function CheckAuth()
    {
        $token = $this->headers["Authorization"];
        $result = $this->db->query("SELECT * FROM `tokens` WHERE `token` = '$token'");

        $this->result = array(
            ("status code") => 401,
            ("status text") => "Unauthorized",
            ("body") => array(
                ("message") => "Unauthorized"
            )
        );

        return $result->num_rows > 0;
    }

    private function Add_comment()
    {
        $product_id = $this->url[1];
        $result = $this->db->query("SELECT `id` FROM `products` WHERE `id` = '$product_id'");
        if ($result->num_rows === 0) {
            $this->result = array(
                ("status code") => 404,
                ("status text") => "Product not found",
                ("body") => array(
                    ("message") => "Product not found"
                )
            );
            return;
        }
        $author = $_POST['author'];
        $comment = $_POST['comment'];

        if (empty($author)) {
            if ($this->CheckAuth())
                $author = "admin";
            else
                $this->messages["author"] = 'author is empty';
        }


        if (strlen($comment) == 0)
            $this->messages["comment"] = 'comment is empty';
        if (strlen($comment) > 255)
            $this->messages["comment"] = 'comment more 255';

        if (count($this->messages) === 0) {
            if ($this->db->query("INSERT INTO `comments` (`author`,`comment`,`product`) VALUES ('$author','$comment','$product_id')")) {
                $this->result = array(
                    ("status code") => 201,
                    ("status text") => "Successful creation",
                    ("body") => array(
                        ("status") => true
                    )
                );
            }
        } else {
            $this->result = array(
                ("status code") => 400,
                ("status text") => "Creating error",
                ("body") => array(
                    ("status") => false,
                    ('massages') => $this->messages
                )
            );
        }
    }

    private function Del_comment()
    {
        $product_id = $this->url[1];
        $comment_id = $this->url[3];

        $result = $this->db->query("SELECT `id` FROM `products` WHERE `id` = '$product_id'");
        if ($result->num_rows === 0) {
            $this->result = array(
                ("status code") => 404,
                ("status text") => "Product not found",
                ("body") => array(
                    ("message") => "Product not found"
                )
            );
            return;
        }

        $result = $this->db->query("SELECT `id` FROM `comments` WHERE `id` = '$comment_id'");
        if ($result->num_rows === 0) {
            $this->result = array(
                ("status code") => 404,
                ("status text") => "Comment not found",
                ("body") => array(
                    ("message") => "Comment not found"
                )
            );
            return;
        }
        if($this->db->query("DELETE FROM `comments` WHERE `id` = '$comment_id'")){
            $this->result = array(
                ("status code") => 201,
                ("status text") => "Successful delete",
                ("body") => array(
                    ("status") => true
                )
            );
        }
    }

    private function Search_product()
    {

        $tag = $this->url[2];
        $sql = "SELECT * FROM `tags` WHERE `tag` = '$tag'GROUP BY `tags`.`product`";
        $result = $this->db->query($sql);
        $products =[];
        if($result->num_rows>0){
            while($row = $result->fetch_array()){
                $product_id = $row['product'];
                $result2 = $this->db->query("SELECT * FROM `products` WHERE `id` = '$product_id'");
                $product = $result2->fetch_array();
                $tags = [];
                $result3 = $this->db->query("SELECT `tag` FROM `tags` WHERE `product` = '$product_id'");
                while($tag = $result3->fetch_array()){
                    $tags[] = $tag[0];
                }
                $products[] = array(
                    ("title")=>$product['title'],
                    ("datatime")=>$product['date'],
                    ("manufacturer")=>$product['manufacturer'],
                    ("text")=>$product['text'],
                    ("tags")=>$tags,
                    ("image")=>$product['image']
                );
            }
        }
        $this->result = array(
            ("status code") => 200,
            ("status text") => "Found product",
            ("body") => $products
        );

    }

}
