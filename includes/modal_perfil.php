<!-- /includes/modal_perfil.php -->
<div class="modal fade" id="perfilModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="perfilModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="perfilModalLabel">Complete seu perfil</h5>
            </div>
            <div class="modal-body">
                <form id="formPerfil" method="POST" action="../ajax/salvar_perfil.php">
                    <div class="mb-3">
                        <label for="nome_negocio" class="form-label">Nome do seu negócio</label>
                        <input type="text" class="form-control" id="nome_negocio" name="nome_negocio" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="segmento" class="form-label">Segmento</label>
                        <select class="form-select" id="segmento" name="segmento" required>
                            <option value="">Selecione...</option>
                            <option value="varejo">Varejo</option>
                            <option value="servicos">Serviços</option>
                            <option value="alimentacao">Alimentação</option>
                            <option value="saude">Saúde</option>
                            <option value="educacao">Educação</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="publico_alvo" class="form-label">Público-alvo principal</label>
                        <input type="text" class="form-control" id="publico_alvo" name="publico_alvo" required>
                    </div>

                    <div class="mb-3">
                        <label for="objetivo_principal" class="form-label">Objetivo principal com o WhatsApp</label>
                        <select class="form-select" id="objetivo_principal" name="objetivo_principal" required>
                            <option value="">Selecione...</option>
                            <option value="vendas">Aumentar vendas</option>
                            <option value="atendimento">Melhorar atendimento</option>
                            <option value="leads">Captar leads</option>
                            <option value="fidelizacao">Fidelizar clientes</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Concluir</button>
                </form>
            </div>
        </div>
    </div>
</div>