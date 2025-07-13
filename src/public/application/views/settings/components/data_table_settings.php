`
<div>
    <h3> {{el.category && el.category.toUpperCase()}}</h3>
    <div class="blue-border" v-if="el.category">
        <table :id="sanitizeTitle(el.category)+'-all'" class="table table-hover table-bordered wrap"
               style="width:100%; padding-top:15px">
            <thead>
            <tr>
                <th></th>
                <th>Nome da configuração</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="item in el.settings" class="linha-ciclo-loja">
                <td>{{item.name}}</td>
                <td style="width:25%" data-toggle="tooltip" data-placement="top" :data-tooltip="item.name">
                    {{item.friendly_name}}
                </td>
                <td style="width:50%;word-break: break-word;">
                    {{splitString(item.description, 15, 5)}}{{splitString(item.description, 15, 0) !== '' && show !=
                    item.id ? '...' : ''}}
                    <div v-if="item.description.length > 0 && splitString(item.description, 15, 0) !== ''"
                         class="anim-block" :class="show == item.id ? 'open' : ''">
                        <div class="content-description" v-html="splitString(item.description, 10, 0)">
                        </div>
                    </div>

                    <button @click="resetFn(item.id)"
                            v-if="item.description.length > 0 && splitString(item.description, 15, 0) !== ''"
                            type="button" class="btn btn-block btn-default ver-mais edit" style="border: 0;width: auto">
                        <i class="fa " :class="show != item.id ? 'fa-angle-down' : 'fa-angle-up'"
                           aria-hidden="true"></i> Ver {{show != item.id ? 'mais' : 'menos'}}
                    </button>

                </td>
                <td style="width:20%;text-align:left;word-break: break-word;">
                    {{item.value}}
                </td>
                <td style="width:5%;text-align:center">
                    <span v-if="item.status == 1" class="badge badge-success navbar-badge">Ativo</span>
                    <span v-if="item.status != 1" class="badge badge-danger navbar-badge">Inativo</span>
                </td>
                <td style="width:5%">
                    <button type="button" class="btn btn-block btn-default edit" @click="editSetting(item)">
                        <i class="fa fa-pen"></i> Editar valor
                    </button>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>`