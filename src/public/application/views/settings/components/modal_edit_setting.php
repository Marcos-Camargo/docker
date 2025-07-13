<div class="modal fade" id="edit-setting" style="display: none" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:100%">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="float: left;">Editar configuração</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <form role="form" id="formCycle" method="post" @submit.prevent="saveSetting">
                <input type="hidden" name="settings_id" v-model.trim="settingData.id">
                <input type="hidden" name="edit_setting_name_old" v-model.trim="settingData.name">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="">Nome da configuração</label>
                            <input class="form-control" type="text" v-model.trim="settingData.name" required readonly>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="">Nome Amigável</label>
                            <input class="form-control" type="text" v-model.trim="settingData.friendly_name" required>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="">Descrição</label>
                            <textarea class="form-control" v-model.trim="settingData.description" cols="5" rows="4"></textarea>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-8">
                            <label for="">Valor</label>
                            <input class="form-control" type="text" v-model.trim="settingData.value" required>
                        </div>
                        <div class="col-md-4">
                            <label for="">Status</label>
                            <select class="form-control" v-model.trim="settingData.status">
                                <option :selected="settingData.status != '1'" value="2">Inativo</option>
                                <option :selected="settingData.status == '1'" value="1">Ativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Fechar
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="buttonDisable" style="display: flex; float: right;">
                            {{buttonDisable ? '' : 'Salvar'}}
                            <div class="overlay" v-show="buttonDisable">
                                <i class="fas fa-1x fa-sync-alt fa-spin" style="margin-right: 4px;"></i> Aguarde...
                            </div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="add-setting" style="display: none" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:100%">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="float: left;">Adicionar parâmetro</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <form role="form" id="formCycle" method="post" @submit.prevent="insertSetting">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="">Nome da configuração</label>
                            <input class="form-control" type="text" v-model.trim="settingAddData.name" required>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="">Nome Amigável</label>
                            <input class="form-control" type="text" v-model.trim="settingAddData.friendly_name" required>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="">Descrição</label>
                            <textarea class="form-control" v-model.trim="settingAddData.description" cols="5" rows="3"></textarea>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-8">
                            <label for="">Valor</label>
                            <input class="form-control" type="text" v-model.trim="settingAddData.value" required>
                        </div>
                        <div class="col-md-4">
                            <label for="">Status</label>
                            <select class="form-control" v-model.trim="settingAddData.status">
                                <option :selected="settingAddData.status != '1'" value="2">Inativo</option>
                                <option :selected="settingAddData.status == '1'" value="1">Ativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Fechar
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="buttonDisable" style="display: flex; float: right;">
                        {{buttonDisable ? '' : 'Salvar'}}
                        <div class="overlay" v-show="buttonDisable">
                            <i class="fas fa-1x fa-sync-alt fa-spin" style="margin-right: 4px;"></i> Aguarde...
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>