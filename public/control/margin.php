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
                <form action="action.php?action=addMargin" method="post" class="form-horizontal form-material"
                      _lpchecked="1">
                    <div class="form-group">
                        <h3>Add new margin</h3>
                        <label class="col-md-12">From price (only integer, 50.00 = 5000)</label>
                        <div class="col-md-12">
                            <input type="text" name="fromPrice" placeholder="5000"
                                   class="form-control form-control-line"
                                   style="cursor: auto;">
                        </div>
                        <label class="col-md-12">To price (only integer, 100.00 = 10000)</label>
                        <div class="col-md-12">
                            <input type="text" name="toPrice" placeholder="10000"
                                   class="form-control form-control-line"">
                        </div>
                        <label class="col-md-12">Margin (in %)</label>
                        <div class="col-md-12">
                            <input type="text" name="marginPercent" placeholder="25"
                                   class="form-control form-control-line"">
                        </div>
                        <label class="col-md-12">Description</label>
                        <div class="col-md-12">
                            <textarea name="description" class="form-control"></textarea>
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
                <?php include('modules/margins.php'); ?>
            </div>
        </div>
    </div>
    <?php include('blocks/footer.php'); ?>
