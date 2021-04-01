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
                <form action="action.php?action=addCategory" method="post" class="form-horizontal form-material">
                    <div class="form-group">
                        <h3>Add new category</h3>
                        <label class="col-md-12">Title</label>
                        <div class="col-md-12">
                            <input type="text" name="title" placeholder="Handbags"
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
                <?php include('modules/categories.php'); ?>
            </div>
        </div>
    </div>
    <?php include('blocks/footer.php'); ?>
