
<div class="flex3 mt-4">
    <div class="flex-items">
        <button style="float:right" type="button" v-if="sanitizeTitle(settings.category) == 'personalizado'"
                class="btn btn-primary add skin-conectala mt-3 mb-4"
                @click="addSetting()">
            <i class="fa fa-plus"></i> Adicionar parâmetro
        </button>
    </div>
    <div class="flex-items">
        <div class="input-group mt-3">
            <input type="search" @keyup="searchData" v-model="search"  style="width:450px;float:right" :id="'search'+sanitizeTitle(settings.category)" class="form-control search" placeholder="Digite para pesquisar uma configuração específica" aria-label="Search" aria-describedby="basic-addon1">
            <span class="input-group-addon"><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
        </div>
    </div>
</div>