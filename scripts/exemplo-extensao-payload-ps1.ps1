# =============================================================================
# Exemplo: campos extras no JSON enviado ao n8n (merge no seu script real)
# Data referência: 30/03/2026
#
# EVIDÊNCIA: o dashboard PHP lê backup_paths e warning_signature a partir do
# payload gravado em raw_payload_json / colunas da tabela backup_execution_events.
#
# 1) backup_paths  — array de strings com as pastas do restic backup (mesmo $paths).
# 2) warning_signature — opcional; se preenchido com "tipo:caminho", a UI mostra o caminho.
#    Nos envios atuais do PS1 Piloto este campo costuma vir vazio e o WF-01 grava NULL.
# =============================================================================

# Exemplo de inclusão no [ordered]@{} do $resultado (antes do ConvertTo-Json):

# backup_paths           = @($paths)
# warning_signature      = $null
# if ($fileInUseCount -gt 0 -and $null -ne $primeiroPathDetectadoNoLog) {
#     warning_signature  = "file_in_use:$primeiroPathDetectadoNoLog"
# }

# Onde $paths já existe no script (ex.: @("C:\pasta1", "C:\pasta2")).
# A extração de $primeiroPathDetectadoNoLog depende do texto real do stderr do Restic
# na sua língua/versão — não copie regex sem validar com um .log real.

Write-Host "Este ficheiro é apenas documentação executável (comentários). Copie os campos para o seu backup_monitorado_*.ps1."
