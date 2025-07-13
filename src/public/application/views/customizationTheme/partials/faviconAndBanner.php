<div class="row">
    <br>
    <div class="box-header with-border">
        <h3 class="box-title">Imagens</h3>
    </div>
    <br>

    <div class="col-sm-3">
        <span class="label label-default text-black" style="font-size: inherit;">Aceito apenas no formato ICO</span><br><br>
        <span class="text-black">Favicon</span>
        <input type="file" id="inputFavicon" name="inputFavicon" accept="image/ico" oninput="pic1.src=window.URL.createObjectURL(this.files[0])"><br>
        <img id="pic1" src="<?= $favicon ?>" style="width: 32px" />
        <br /><br />
        <a href="void:(0)" class="btn btn-danger" id="clearFavicon">x</a>
        <?php if($favicon != ''){ ?>
            <a href="void:(0)" class="btn btn-danger" id="btnComfimFavicon" onclick="QuestionConfirm('Favicon')" >x</a>
        <?php } ?>
    </div>

    <div class="col-sm-9">
        <span class="label label-default" style="font-size: inherit;">Aceito apenas no formato JPG e JPEG</span><br><br>
        <span class="text-black">Banner</span>
        <input type="file" id="inputBanner" name="inputBanner" accept="image/jpeg" oninput="pic2.src=window.URL.createObjectURL(this.files[0])"><br>
        <img id="pic2" src="<?= $banner ?>" style="width: 100%" />
        <br /><br />
        <a href="void:(0)" class="btn btn-danger" id="clearBanner">x</a>
        <?php if($banner != ''){ ?>
            <a href="void:(0)" class="btn btn-danger" id="btnComfimBanner" onclick="QuestionConfirm('Banner')">x</a>
        <?php } ?>
    </div>


</div>