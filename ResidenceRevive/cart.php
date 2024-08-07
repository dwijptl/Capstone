<?php

session_start();

//  only logged in user can visit cart
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

require 'config/db.php';
include 'includes/functions.php';

$categories = getAllCategories($conn);

// Assume user email is stored in session
$email = $_SESSION['email'];

// empty cart
if (isset($_GET['action']) && $_GET['action'] == 'empty') {
    $sql = "DELETE FROM cart WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// remove item from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];

    $sql = "DELETE FROM cart WHERE email = ? AND service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $service_id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// update item from cart
if (isset($_POST['action'], $_POST['service_id'], $_POST['quantity']) && $_POST['action'] == 'update') {


    $service_id = $_POST['service_id'];
    $quantity = $_POST['quantity'];

    if (!$quantity || $quantity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Quantity cannot be Zero.']);
        exit;
    }

    $sql = "UPDATE cart SET quantity = ? WHERE email = ? AND service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $quantity, $email, $service_id);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Quantity updated.']);
    exit;
}

$sql = "SELECT 
        cart.quantity, 
        services.service_name, 
        cart.service_id, 
        services.service_img, 
        services.price AS service_price, 
        services.description AS service_description, 
        categories.category_name, 
        sub_categories.sub_category_name
    FROM 
        cart
    JOIN 
        services ON cart.service_id = services.service_id
    JOIN 
        categories ON cart.category_id = categories.category_id
    JOIN 
        sub_categories ON cart.subcategory_id = sub_categories.sub_category_id
    WHERE 
        cart.email = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$cart_total = 0;
$cart_items = $result->num_rows > 0;

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Residence Revive offers hassle-free and reliable home services, including housekeeping, pest control, appliance repair, and more. Our team of professionals ensures your home is in perfect condition using advanced techniques and eco-friendly products.">
    <title>Cart - Residence Revive</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .alert {
            padding: 20px;
            background-color: #2C559B;
            color: white;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .alert.alert-danger {
            background-color: #2C559B;
            color: white;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f2f2f2;
        }

        .table tbody+tbody {
            border-top: 2px solid #dee2e6;
        }

        .table .img-fluid {
            max-width: 60px;
            height: auto;
            border-radius: 5px;
        }

        .table .btn-danger {
            background-color: #2C559B !important;
            border-color: #2C559B !important;
        }

        .table .btn-danger:hover {
            background-color: #2C559B;
            
        }

        .table .btn-primary {
            background-color: #2C559B;
            border-color: #2C559B;
        }

        .table .btn-primary:hover {
            background-color: #2C559B;
            border-color: #2C559B;
        }

        .cart-total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .cart-buttons {
            text-align: right;
            margin-top: 20px;
        }
        .visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}


        .cart-buttons button {
            background-color: #2C559B !important;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }

        .cart-buttons button:hover {
            background-color: #2C559B;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-4">
        <?php if (isset($_GET['message']) && $_GET['message'] == 'removed'): ?>
        <div class="alert">Service removed from Cart.</div>
        <?php endif; ?>

        <h2 class="mb-3">Cart Summary</h2>

        <div class="text-center mb-3">
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=empty"
                            class="btn btn-danger" style="background-color: #2C559B; border-color: #2C559B ">Empty Cart</a>
                   
        </div>


        <?php if ($cart_items): ?>
            <div class="table-responsive">
        <table class="table border" style="vertical-align: middle;">
            <thead>
                <tr>
                    <th scope="col">Service</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">Price</th>
                    <th >
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cart_service = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class=" d-flex p-3 rounded w-75">
                            <!-- <img src="<?php echo $cart_service['service_img']; ?>"
                            class="img-fluid object-fit-contain" alt="Image"> -->
                            <div class="p-2 ms-2">
                                <p><?php echo $cart_service['service_name']; ?>
                                </p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <form
                            action="<?php echo $_SERVER['PHP_SELF']; ?>"
                            class="d-flex align-items-center">

                            <input type="hidden" value="update" name="action">

                            <input type="hidden" id="service_id"
                                value="<?php echo $cart_service['service_id']; ?>"
                                name="service_id">
                                <label for="quantity-<?php echo $cart_service['service_id']; ?>" class="visually-hidden">
                                Quantity for <?php echo $cart_service['service_name']; ?>
                            </label>
<input type="number" class="me-1 form-control rounded-3 w-50 quantity-input" name="quantity"
                                id="quantity" min="1" autocomplete="off"
                                id="quantity-<?php echo $cart_service['service_id']; ?>"
                                data-service-id="<?php echo $cart_service['service_id']; ?>"
                                value="<?php echo $cart_service['quantity']; ?>">
                        </form>
                    </td>

                    <td>
                        <p class="fw-bold mt-3">
                            <?php echo $cart_service['service_price']; ?>
                        </p>
                        <?php $cart_total += $cart_service['service_price'] * $cart_service['quantity']; ?>
                    </td>
                    <td>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=remove&service_id=<?php echo $cart_service['service_id']; ?>"
                            class="btn text-danger fw-bold fs-3">&times;</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
                </table>



                        <?php

                        $tax_rate = 0.13;

            $tax_amount = $cart_total * $tax_rate;
            $total_with_tax = $cart_total + $tax_amount;

            ?>

            <div class="col-md-6 mx-md-auto">

            <table class="table table-bordered fw-bold">

                            <tr>
                                <td>Cart Total:</td>
                                <td>$<?php echo number_format($cart_total, 2); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Tax Amount (13% of Cart Total):</td>
                                <td>$<?php echo number_format($tax_amount, 2); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Total with Tax:</td>
                                <td>$<?php echo number_format($total_with_tax, 2); ?>
                                </td>
                            </tr>
                        </table>
                
                               <form action="billing.php" method="POST" class="cart-buttons text-center">
                                   <button type="submit">Proceed to Payment</button>
                               </form>

            </div>

                    
         </div>
                     
        <?php else: ?>
        <div class="alert alert-danger">No Services in Cart</div>
        <?php endif; ?>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', updateQuantity);
            });
        });

        // update service quantity
        function updateQuantity() {

            let input = event.target;
            let service_id = input.getAttribute('data-service-id');
            let quantity = input.value;


            fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'update',
                        service_id: service_id,
                        quantity: parseInt(quantity)
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        console.log(data.message);
                        // refresh
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    console.log('An error occurred while updating the quantity.');
                });

        }
    </script>

</body>

</html>