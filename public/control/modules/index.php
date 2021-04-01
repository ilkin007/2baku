<?php
$urlPath = 'http://' . $_SERVER[ 'HTTP_HOST' ];
$zalando = 'zalando_run';
$general = 'general_run';
?>
<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
</head>
<body>
<h2>What do you want to parse?</h2>

<form style="width: 45%; float: left;" method="post" action="action.php?action=addToQueue">
    <h3>Add link to process</h3>
    <p>
        <label for="category">Category:</label>
        <br>
        <select name="category">
            <option value="handbags">Handbags</option>
            <option value="handbags">Maternity clothes</option>
        </select>
    </p>
    <p>
        <label for="link">Url:</label>
        <br>
        <input name="link" maxlength="800"/>
    </p>
    <p>
        <label>Type of link</label>
        <br>
        <select name="type">
            <option value="product">product</option>
            <option value="category">category</option>
        </select>
    </p>
    <p><input type="submit" value="Add to the queue"/></p>
</form>

<form style="width: 45%; float: left;" method="post" action="action.php?action=addMargin">
    <h3>Add margin</h3>
    <p>
        <label>From price, float with . (point) as delimiter</label>
        <br>
        <input type="text" name="fromPrice" placeholder="0.00"/>
    </p>
    <p>
        <label>To price, float with . (point) as delimiter</label>
        <br>
        <input type="text" name="toPrice" placeholder="150.00"/>
    </p>
    <p>
        <label>Margin in (%, as integer), ex.: 25</label>
        <br>
        <input type="text" name="marginPercent" placeholder="25"/>
    </p>
    <p>
        <label>Description (if necessary)</label>
        <br>
        <textarea name="description"></textarea>
    </p>
    <p><input type="submit" value="Add"/></p>
</form>

<div style="float: right; width: 45%;">
    <h2 style="color: red;">Margins</h2>

    <?php include('margins.php'); ?>
</div>


<div style="clear: both; height: 30px;"></div>
<h2 style="color: red;">Queue</h2>

<?php include('queue.php'); ?>


<h2 style="color: red;">What can I do?</h2>
<ul>
    <li>
        <a target="_blank" href="<?= $urlPath ?>/zalando/taskrunner.php">
            Parse from Zalando
        </a>
    <li>
        <a target="_blank" href="<?= $urlPath ?>/zalando/synchronize.php">
            Synchronize
        </a>
    </li>
    <li><a target="_blank" href="<?= $urlPath ?>/parser/Selenium/App/Zalando/log/">
            Logs
        </a>
    </li>
</ul>

</body>
</html>
