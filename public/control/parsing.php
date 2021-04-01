<?php include('blocks/header.php'); ?>
<aside class="left-sidebar">
    <div class="scroll-sidebar">
        <?php include('blocks/menu.php'); ?>
    </div>
</aside>
<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-5 col-8 align-self-center">

            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-4">
                <form class="form-horizontal form-material" action="action.php?action=addToQueue" method="post">
                    <div class="form-group">
                        <h3>Add new link to parse</h3>
                        <label class="col-md-12">Link</label>
                        <div class="col-md-12">
                            <input type="text" name="link" placeholder="https://somewebsite.com/product1.html"
                                   class="form-control form-control-line"
                                   style="cursor: auto;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-12">Type</label>
                        <div class="col-sm-12">
                            <select name="type" class="form-control form-control-line">
                                <option value="category">Category</option>
                                <option value="product">Product</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-12">Select Category</label>
                        <div class="col-sm-12">
                            <select name="category_id" class="form-control form-control-line">
                                <?php include('modules/categoriesOptions.php'); ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-12">Minimum stock (only for type category)</label>
                        <div class="col-md-12">
                            <input type="text" name="minimum" placeholder="0"
                                   class="form-control form-control-line"
                                   style="cursor: auto;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-12">Limit / Maximum in stock (only for type category)</label>
                        <div class="col-md-12">
                            <input type="text" name="limit" placeholder="0"
                                   class="form-control form-control-line"
                                   style="cursor: auto;">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-12">
                            <button class="btn btn-success">Add</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-8 col-md-8">
                <?php include('modules/queue.php'); ?>
            </div>
        </div>
    </div>
    <?php include('blocks/footer.php'); ?>
