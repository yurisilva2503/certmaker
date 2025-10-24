let alunosProcessados = [];
let alunoAtual = null;
let currentTab = 0;
let canvas = null;
let ctx = null;
let modelo = null;
let paginas = [];
let paginaAtualIndex = 0;
let elementosPaginaAtual = [];
let backgroundImages = [];
let mmW = 0;
let mmH = 0;
let DPI = 120;
let EXPORT_DPI = 200;
const { jsPDF } = window.jspdf;
let placeholdersEncontradas = new Set();
let valoresGlobais = {};
let quillEditors = {}; 

const TAGS_ESPECIFICAS = [
    'local', 'curso', 'cargahoraria', 'periodo', 
    'conteudoprogramatico', 'data', 'instituicao',
    'coordenador', 'ministrante', 'cidade', 'estado'
];

const TODAS_TAGS_DISPONIVEIS = [
    '{nome}', '{cpf}', '{curso}', '{cargahoraria}', '{local}', '{data}',
    '{instituicao}', '{coordenador}', '{ministrante}', '{cidade}', '{estado}',
    '{periodo}', '{conteudoprogramatico}', '{qrcode}'
];


function extractTextLines(htmlContent) {
    if (!htmlContent) return [''];
    
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlContent, 'text/html');
    const lines = [];
    
    function processNode(node, currentLine = '') {
        if (node.nodeType === Node.TEXT_NODE) {
            return currentLine + node.textContent;
        } else if (node.nodeType === Node.ELEMENT_NODE) {
            const tagName = node.tagName.toLowerCase();
            
            if (tagName === 'ul' || tagName === 'ol') {
                Array.from(node.children).forEach(li => {
                    const listItemContent = processNode(li);
                    const marker = tagName === 'ul' ? '• ' : (Array.from(node.children).indexOf(li) + 1) + '. ';
                    lines.push(marker + listItemContent);
                });
                return currentLine;
            }
            else if (tagName === 'li') {
                let liContent = '';
                Array.from(node.childNodes).forEach(child => {
                    liContent += processNode(child, '');
                });
                return liContent;
            }
            else if (tagName === 'br') {
                lines.push(currentLine);
                return '';
            }
            else if (tagName === 'p' || tagName === 'div') {
                if (currentLine) {
                    lines.push(currentLine);
                }
                let pContent = '';
                Array.from(node.childNodes).forEach(child => {
                    pContent = processNode(child, pContent);
                });
                if (pContent) {
                    lines.push(pContent);
                }
                return '';
            }
            else {
                let elementContent = '';
                Array.from(node.childNodes).forEach(child => {
                    elementContent = processNode(child, elementContent);
                });
                
                // Preservar todas as tags de formatação do Quill
                if (tagName === 'strong' || tagName === 'b') {
                    return currentLine + `<strong>${elementContent}</strong>`;
                } else if (tagName === 'em' || tagName === 'i') {
                    return currentLine + `<em>${elementContent}</em>`;
                } else if (tagName === 'u') {
                    return currentLine + `<u>${elementContent}</u>`;
                } else if (tagName === 'span') {
                    // Preservar spans que podem conter estilos do Quill
                    const style = node.getAttribute('style') || '';
                    return currentLine + `<span style="${style}">${elementContent}</span>`;
                } else {
                    return currentLine + elementContent;
                }
            }
        }
        return currentLine;
    }
    
    // Processar todos os nós do body
    Array.from(doc.body.childNodes).forEach(node => {
        processNode(node, '');
    });
    
    // Filtrar linhas vazias
    const filteredLines = lines.filter(line => line.trim() !== '');
    return filteredLines.length > 0 ? filteredLines : [''];
}

function getTextXPosition(el, scaleFactor = 1) {
    const scaledW = el.w * scaleFactor;
    const scaledX = el.x * scaleFactor;
    const padding = 2 * scaleFactor;
    
    switch (el.align) {
        case "center":
            return scaledX + scaledW / 2;
        case "right":
            return scaledX + scaledW - padding;
        default:
            return scaledX + padding;
    }
}

// FUNÇÃO COMPLETAMENTE REFEITA PARA COMBINAR FORMATACOES CORRETAMENTE
function renderFormattedLine(ctx, line, x, y, el, scaleFactor = 1) {
    const parser = new DOMParser();
    
    // Processar o HTML mantendo TODAS as tags
    const doc = parser.parseFromString(`<div>${line}</div>`, 'text/html');
    const container = doc.body.firstChild;
    
    ctx.textBaseline = "alphabetic";
    ctx.textAlign = "left";
    
    // Primeira passagem: calcular largura total
    let totalWidth = 0;
    const textSegments = [];
    
    // Função recursiva para processar nós e coletar formatações
    function processNode(node, currentStyles = {}) {
        const styles = { ...currentStyles };
        
        if (node.nodeType === Node.ELEMENT_NODE) {
            const tagName = node.tagName.toLowerCase();
            const style = node.style;
            
            // ATUALIZAR ESTILOS BASEADOS NA TAG E ESTILOS INLINE
            // Negrito - pode ser combinado com outros estilos
            if (tagName === 'strong' || tagName === 'b' || style.fontWeight === 'bold') {
                styles.fontWeight = 'bold';
            }
            // Itálico - pode ser combinado com outros estilos
            if (tagName === 'em' || tagName === 'i' || style.fontStyle === 'italic') {
                styles.fontStyle = 'italic';
            }
            // Sublinhado - pode ser combinado com outros estilos
            if (tagName === 'u' || style.textDecoration === 'underline') {
                styles.textDecoration = 'underline';
            }
            
            // Processar todos os filhos com os estilos acumulados
            Array.from(node.childNodes).forEach(child => {
                processNode(child, styles);
            });
            
        } else if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent;
            if (!text.trim()) return;
            
            // CONSTRUIR FONT STRING COMBINANDO TODOS OS ESTILOS
            const fontWeight = styles.fontWeight || 'normal';
            const fontStyle = styles.fontStyle || 'normal';
            const fontSize = el.fontSize * scaleFactor;
            const fontFamily = el.font || "Helvetica";
            
            const fontString = `${fontWeight} ${fontStyle} ${fontSize}px ${fontFamily}`;
            
            ctx.font = fontString;
            const width = ctx.measureText(text).width;
            totalWidth += width;
            
            textSegments.push({
                content: text,
                width,
                font: fontString,
                color: el.color || "#222",
                textDecoration: styles.textDecoration || 'none'
            });
        }
    }
    
    // Processar todos os nós começando com estilos vazios
    Array.from(container.childNodes).forEach(node => {
        processNode(node, {});
    });
    
    // Ajustar posição X baseado no alinhamento
    let startX = x;
    if (el.align === "center") {
        startX = x - totalWidth / 2;
    } else if (el.align === "right") {
        startX = x - totalWidth;
    }
    
    // Segunda passagem: renderizar
    let currentX = startX;
    
    for (const segment of textSegments) {
        ctx.font = segment.font;
        ctx.fillStyle = segment.color;
        
        ctx.fillText(segment.content, currentX, y);
        
        // Aplicar sublinhado se necessário
        if (segment.textDecoration === "underline") {
            ctx.strokeStyle = segment.color;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(currentX, y + 2);
            ctx.lineTo(currentX + segment.width, y + 2);
            ctx.stroke();
        }
        
        currentX += segment.width;
    }
}

// Função principal para renderizar texto formatado
function renderFormattedText(ctx, el, scaleFactor, data) {
    let text = el.text;
    if (data) {
        text = text.replace(/\{([^}]+)\}/gi, (match, tag) => {
            const tagLower = tag.toLowerCase();
            for (const key in data) {
                if (key.toLowerCase() === tagLower) {
                    // PARA TODOS OS CAMPOS COM QUILL, USAR O HTML DIRETAMENTE
                    if (TAGS_ESPECIFICAS.includes(tagLower) && data[key]) {
                        return data[key]; // Retorna o HTML formatado do Quill
                    }
                    return data[key] || '';
                }
            }
            return match; // Mantém a tag se não encontrar substituição
        });
    }
    
    const lines = extractTextLines(text);
    const lineHeight = (el.fontSize * scaleFactor) * 1.2;
    
    // Calcular posição Y baseada no alinhamento vertical
    let startY;
    const totalTextHeight = lines.length * lineHeight;
    
    switch (el.verticalAlign || "middle") {
        case "top":
            startY = (el.y * scaleFactor) + (el.fontSize * scaleFactor);
            break;
        case "bottom":
            startY = (el.y * scaleFactor) + (el.h * scaleFactor) - totalTextHeight + (el.fontSize * scaleFactor);
            break;
        case "middle":
        default:
            startY = (el.y * scaleFactor) + ((el.h * scaleFactor) - totalTextHeight) / 2 + (el.fontSize * scaleFactor);
            break;
    }

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (!line) continue;
        
        const x = getTextXPosition(el, scaleFactor);
        renderFormattedLine(ctx, line, x, startY + (i * lineHeight), el, scaleFactor);
    }
}

// Inicializar Quill Editor para um campo específico
function inicializarQuillEditorParaCampo(tag) {
    const editorId = `quillEditor_${tag}`;
    const textareaId = `global_${tag}`;
    
    const toolbarOptions = [
        ['bold', 'italic', 'underline'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['clean']
    ];

    const quill = new Quill(`#${editorId}`, {
        modules: {
            toolbar: toolbarOptions
        },
        theme: 'snow',
        formats: ['bold', 'italic', 'underline', 'list']
    });

    // Converter conteúdo existente para formato do Quill
    const textarea = document.getElementById(textareaId);
    if (textarea && textarea.value) {
        quill.root.innerHTML = textarea.value;
    }

    // Atualizar o textarea hidden quando o conteúdo mudar
    quill.on('text-change', function() {
        const htmlContent = quill.root.innerHTML;
        document.getElementById(textareaId).value = htmlContent;
        
        // Forçar atualização da prévia
        if (alunoAtual) {
            render(alunoAtual);
        }
    });

    // Armazenar o editor no objeto global
    quillEditors[tag] = quill;
    
    return quill;
}

// Função para criar os inputs para tags específicas (COM QUILL PARA TODOS OS CAMPOS)
function criarCamposGlobais() {
    const container = document.getElementById('camposGlobais');
    let html = '';
    
    TAGS_ESPECIFICAS.forEach(tag => {
        if (Array.from(placeholdersEncontradas).some(ph => ph.toLowerCase().includes(tag.toLowerCase()))) {
            const placeholderCompleto = `{${tag}}`;
            
            // PARA TODOS OS CAMPOS, USAR QUILL EDITOR
            html += `
                <div class="col-md-12 mb-3">
                    <label for="global_${tag}" class="form-label">${tag.charAt(0).toUpperCase() + tag.slice(1)}</label>
                    <div id="quillEditor_${tag}" style="height: 150px;"></div>
                    <textarea class="form-control d-none" id="global_${tag}" rows="4"
                           placeholder="Valor para ${placeholderCompleto}"></textarea>
                    <div class="form-text">Substituirá ${placeholderCompleto} em todos os certificados</div>
                </div>
            `;
        }
    });
    
    if (!html) {
        html = '<div class="col-12"><p class="text-muted">Nenhuma tag específica detectada neste modelo.</p></div>';
        return;
    }
    
    container.innerHTML = html;
    
    // Inicializar Quill Editor para todos os campos detectados
    TAGS_ESPECIFICAS.forEach(tag => {
        if (Array.from(placeholdersEncontradas).some(ph => ph.toLowerCase().includes(tag.toLowerCase()))) {
            inicializarQuillEditorParaCampo(tag);
        }
    });
}

// Função para coletar os valores globais dos inputs (ATUALIZADA PARA QUILL EM TODOS OS CAMPOS)
function coletarValoresGlobais() {
    valoresGlobais = {};
    
    TAGS_ESPECIFICAS.forEach(tag => {
        // Para todos os campos, pegar o HTML do Quill
        if (quillEditors[tag]) {
            valoresGlobais[tag] = quillEditors[tag].root.innerHTML.trim();
        } else {
            const input = document.getElementById(`global_${tag}`);
            if (input) {
                valoresGlobais[tag] = input.value.trim();
            }
        }
    });
    
    return valoresGlobais;
}

// Função para extrair placeholders do modelo
function extrairPlaceholdersDoModelo() {
    placeholdersEncontradas.clear();
    
    if (modelo.elementos && Array.isArray(modelo.elementos)) {
        modelo.elementos.forEach(elemento => {
            if (elemento.type === 'text' && elemento.text) {
                const matches = elemento.text.match(/\{([^}]+)\}/g);
                if (matches) {
                    matches.forEach(match => {
                        placeholdersEncontradas.add(match);
                    });
                }
            }
        });
    }
    
    if (modelo.paginas && Array.isArray(modelo.paginas)) {
        modelo.paginas.forEach(pagina => {
            if (pagina.elementos && Array.isArray(pagina.elementos)) {
                pagina.elementos.forEach(elemento => {
                    if (elemento.type === 'text' && elemento.text) {
                        const matches = elemento.text.match(/\{([^}]+)\}/g);
                        if (matches) {
                            matches.forEach(match => {
                                placeholdersEncontradas.add(match);
                            });
                        }
                    }
                    if (elemento.type === 'qrcode' && elemento.text) {
                        const matches = elemento.text.match(/\{([^}]+)\}/g);
                        if (matches) {
                            matches.forEach(match => {
                                placeholdersEncontradas.add(match);
                            });
                        }
                    }
                });
            }
        });
    }
    
    atualizarExibicaoPlaceholders();
    criarCamposGlobais();
}

// Função para inserir tag no campo ativo (ATUALIZADA PARA QUILL EM TODOS OS CAMPOS)
function inserirTagNoEditor(tag) {
    // Verificar se algum Quill Editor está ativo
    let quillAtivo = null;
    for (const [tagName, quill] of Object.entries(quillEditors)) {
        if (document.activeElement === quill.root) {
            quillAtivo = quill;
            break;
        }
    }
    
    // Se algum Quill estiver ativo, inserir nele
    if (quillAtivo) {
        const selection = quillAtivo.getSelection();
        if (selection) {
            quillAtivo.insertText(selection.index, tag);
        } else {
            quillAtivo.insertText(quillAtivo.getLength(), tag);
        }
        return;
    }
    
    // Para outros campos (inputs e textareas normais)
    const activeElement = document.activeElement;
    if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
        const start = activeElement.selectionStart;
        const end = activeElement.selectionEnd;
        const value = activeElement.value;
        
        activeElement.value = value.substring(0, start) + tag + value.substring(end);
        activeElement.selectionStart = activeElement.selectionEnd = start + tag.length;
        activeElement.focus();
    }
}

// Função para determinar campos necessários
function determinarCamposNecessarios() {
    const campos = new Set();
    const placeholdersPersonalizadas = new Set();
    
    placeholdersEncontradas.forEach(placeholder => {
        const tagNome = placeholder.replace(/{|}/g, '').toLowerCase();
        
        if (TAGS_ESPECIFICAS.includes(tagNome)) {
            return;
        }
        
        switch(tagNome) {
            case 'nome':
                campos.add('Nome');
                break;
            case 'cpf':
                campos.add('CPF');
                break;
            case 'curso':
                if (!TAGS_ESPECIFICAS.includes('curso')) {
                    campos.add('Curso');
                }
                break;
            case 'cargahoraria':
                if (!TAGS_ESPECIFICAS.includes('cargahoraria')) {
                    campos.add('Carga Horária');
                }
                break;
            case 'qrcode':
                break;
            default:
                if (!TAGS_ESPECIFICAS.includes(tagNome)) {
                    placeholdersPersonalizadas.add(placeholder);
                    campos.add(placeholder);
                }
                break;
        }
    });
    
    if (placeholdersPersonalizadas.size > 0) {
        window.placeholdersPersonalizadas = Array.from(placeholdersPersonalizadas);
    }
    
    return Array.from(campos);
}

// Função principal de renderização
async function render(data) {
    return new Promise(async (resolve) => {
        const dadosCompletos = { ...valoresGlobais, ...data };
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        const scaleFactor = DPI / 120;
        const bgToUse = backgroundImages[paginaAtualIndex];
        
        if (bgToUse) {
            ctx.drawImage(bgToUse, 0, 0, canvas.width, canvas.height);
        }

        await renderElements();
        resolve();

        async function renderElements() {
            const imagePromises = [];

            for (const el of elementosPaginaAtual) {
                ctx.save();
                ctx.globalAlpha = el.opacity ?? 1;

                if (el.rotate) {
                    ctx.translate((el.x * scaleFactor) + (el.w * scaleFactor) / 2,
                        (el.y * scaleFactor) + (el.h * scaleFactor) / 2);
                    ctx.rotate(el.rotate * Math.PI / 180);
                    ctx.translate(-((el.x * scaleFactor) + (el.w * scaleFactor) / 2),
                        -((el.y * scaleFactor) + (el.h * scaleFactor) / 2));
                }

                if (el.type === 'text') {
                    renderFormattedText(ctx, el, scaleFactor, dadosCompletos);
                    
                } else if (el.type === 'image' && el.src) {
                    const imgPromise = new Promise((imgResolve) => {
                        const img = new Image();
                        img.crossOrigin = "Anonymous";
                        img.onload = function() {
                            ctx.drawImage(img,
                                el.x * scaleFactor,
                                el.y * scaleFactor,
                                el.w * scaleFactor,
                                el.h * scaleFactor);
                            imgResolve();
                        };
                        img.onerror = function() {
                            console.error('Erro ao carregar imagem:', el.src);
                            imgResolve();
                        };
                        img.src = el.src;
                    });
                    imagePromises.push(imgPromise);

                } else if (el.type === 'qrcode' && el.text) {
                    let qrText = el.text;
                    if (dadosCompletos) {
                        qrText = qrText.replace(/\{([^}]+)\}/gi, (match, tag) => {
                            const tagLower = tag.toLowerCase();
                            for (const key in dadosCompletos) {
                                if (key.toLowerCase() === tagLower) {
                                    return dadosCompletos[key] || '';
                                }
                            }
                            return match;
                        });
                    }
                    
                    try {
                        const typeNumber = 0;
                        const errorCorrectionLevel = 'H';
                        const qr = qrcode(typeNumber, errorCorrectionLevel);
                        qr.addData(qrText);
                        qr.make();
                        
                        const cellSize = Math.min(
                            (el.w * scaleFactor) / qr.getModuleCount(),
                            (el.h * scaleFactor) / qr.getModuleCount()
                        );
                        
                        const offsetX = (el.x * scaleFactor) + ((el.w * scaleFactor) - (qr.getModuleCount() * cellSize)) / 2;
                        const offsetY = (el.y * scaleFactor) + ((el.h * scaleFactor) - (qr.getModuleCount() * cellSize)) / 2;
                        
                        ctx.fillStyle = el.color || '#000000';
                        
                        for (let row = 0; row < qr.getModuleCount(); row++) {
                            for (let col = 0; col < qr.getModuleCount(); col++) {
                                if (qr.isDark(row, col)) {
                                    ctx.fillRect(
                                        offsetX + col * cellSize,
                                        offsetY + row * cellSize,
                                        cellSize,
                                        cellSize
                                    );
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Erro ao gerar QR code:', error);
                    }
                }
                ctx.restore();
            }

            await Promise.all(imagePromises);
        }
    });
}

// Função para processar lista de alunos
function processarListaAlunos() {
    coletarValoresGlobais();

    const listaTexto = document.getElementById('listaAlunos').value.trim();
    const separador = document.getElementById('separador').value;
    const camposNecessarios = determinarCamposNecessarios();

    if (!listaTexto) {
        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text: "Por favor, insira uma lista de alunos.",
            confirmButtonText: "OK",
            confirmButtonColor: "green",
            theme: `${temaAlerta}`,
        })
        return;
    }

    const linhas = listaTexto.split('\n');
    alunosProcessados = [];

    for (let i = 0; i < linhas.length; i++) {
        const linha = linhas[i].trim();
        if (!linha) continue;

        let campos;
        if (separador === 'tab') {
            campos = linha.split('\t');
        } else {
            campos = linha.split(separador);
        }

        campos = campos.map(campo => campo.trim());

        const minCampos = camposNecessarios.length;
        if (campos.length < minCampos) {
            Swal.fire({
                icon: "warning",
                title: "Atenção",   
                text: `Linha ${i + 1} não possui campos suficientes. Esperado: ${minCampos} (${camposNecessarios.join(', ')}), Encontrado: ${campos.length}`,
                confirmButtonText: "OK",
                confirmButtonColor: "green",
                theme: `${temaAlerta}`,
            })
            
            return;
        }

        const aluno = {};
        
        Object.keys(valoresGlobais).forEach(tag => {
            aluno[tag] = valoresGlobais[tag];
        });
        
        camposNecessarios.forEach((campo, index) => {
            if (campo === 'Nome') {
                aluno.nome = campos[index] || '';
            } else if (campo === 'CPF') {
                aluno.cpf = campos[index] || '';
            } else if (campo === 'Curso') {
                if (!TAGS_ESPECIFICAS.includes('curso')) {
                    aluno.curso = campos[index] || '';
                }
            } else if (campo === 'Carga Horária') {
                if (!TAGS_ESPECIFICAS.includes('cargahoraria')) {
                    aluno.cargaHoraria = campos[index] || '';
                }
            } else {
                aluno[campo] = campos[index] || '';
            }
        });

        alunosProcessados.push(aluno);
    }

    if (alunosProcessados.length === 0) {
        Swal.fire({
                icon: "warning",
                title: "Atenção",   
                text: `Nenhum aluno válido foi encontrado na lista.`,
                confirmButtonText: "OK",
                confirmButtonColor: "green",
                theme: `${temaAlerta}`,
            })
        return;
    }

    const resultadoDiv = document.getElementById('listaAlunosProcessados');
    resultadoDiv.innerHTML = `<p>${alunosProcessados.length} aluno(s) processado(s) com sucesso:</p>`;

    const lista = document.createElement('ul');
    alunosProcessados.forEach(aluno => {
        const item = document.createElement('li');
        const info = [];
        
        if (aluno.nome) info.push(`Nome: ${aluno.nome}`);
        if (aluno.cpf) info.push(`CPF: ${aluno.cpf}`);
        
        Object.keys(valoresGlobais).forEach(tag => {
            if (valoresGlobais[tag]) {
                info.push(`${tag}: ${valoresGlobais[tag]}`);
            }
        });
        
        item.textContent = info.join(' - ');
        lista.appendChild(item);
    });
    resultadoDiv.appendChild(lista);

    document.getElementById('resultadoProcessamento').classList.remove('d-none');
}

// Função para salvar certificado no banco de dados
async function salvarCertificadoNoBanco(dadosCertificado) {
    try {
        const response = await fetch('/admin/certificados/salvar-certificado', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(dadosCertificado)
        });
        
        const resultado = await response.json();
        return resultado;
    } catch (error) {
        console.error('Erro ao salvar certificado:', error);
        return { success: false, error: error.message };
    }
}

// Inicialização quando a página carrega
window.onload = function() {
    initCanvas();
    setupEventListeners();
};

// Nova função para carregar backgrounds de todas as páginas
async function carregarBackgrounds() {
    backgroundImages = [];
    
    for (let i = 0; i < paginas.length; i++) {
        const pagina = paginas[i];
        
        if (pagina.background) {
            await new Promise((resolve) => {
                const img = new Image();
                img.onload = function() {
                    backgroundImages[i] = img;
                    resolve();
                };
                img.onerror = function() {
                    console.error('Erro ao carregar background da página', i, pagina.background);
                    backgroundImages[i] = null;
                    resolve();
                };
                img.src = pagina.background;
            });
        } else {
            backgroundImages[i] = null;
        }
    }
}

// Inicializar o canvas
async function initCanvas() {
    canvas = document.getElementById('preview');
    ctx = canvas.getContext('2d');
    modelo = modeloRaw;
    
    normalizeModelStructure();
    extrairPlaceholdersDoModelo();
    
    function safeJsonParse(data, defaultValue = []) {
        if (typeof data === 'string') {
            try {
                return JSON.parse(data);
            } catch (e) {
                console.error('Erro ao fazer parse JSON:', e);
                return defaultValue;
            }
        }
        return data || defaultValue;
    }
    
    if (modelo.paginas) {
        paginas = safeJsonParse(modelo.paginas, []);
    } else if (modelo.elementos) {
        const elementosArray = safeJsonParse(modelo.elementos, []);
        paginas = [{ elementos: elementosArray }];
    } else {
        paginas = [{ elementos: [] }];
    }
    
    if (!Array.isArray(paginas)) {
        paginas = [paginas];
    }
    
    paginas = paginas.map(pagina => {
        return {
            elementos: Array.isArray(pagina.elementos) ? pagina.elementos : [],
            background: pagina.background || null
        };
    });
    
    elementosPaginaAtual = paginas[paginaAtualIndex].elementos;
    
    mmW = modelo.orientacao === 'portrait' ? 210 : 297;
    mmH = modelo.orientacao === 'portrait' ? 297 : 210;
    
    canvas.width = Math.round(mmW / 25.4 * DPI);
    canvas.height = Math.round(mmH / 25.4 * DPI);
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    
    await carregarBackgrounds();
    atualizarIndicadorPagina();
    render();
}

// Nova função para normalizar a estrutura do modelo
function normalizeModelStructure() {
    if (typeof modelo.elementos === 'string') {
        modelo.elementos = JSON.parse(modelo.elementos);
    }
    
    if (typeof modelo.paginas === 'string') {
        modelo.paginas = JSON.parse(modelo.paginas);
    }
    
    if (!modelo.paginas) {
        modelo.paginas = [];
    }
    
    if (modelo.paginas.length === 0 && modelo.elementos && modelo.elementos.length > 0) {
        modelo.paginas = [{
            elementos: modelo.elementos,
            background: modelo.background || null
        }];
    }
    else if (modelo.paginas.length > 0 && 
             (!modelo.paginas[0].elementos || modelo.paginas[0].elementos.length === 0) && 
             modelo.elementos && modelo.elementos.length > 0) {
        modelo.paginas[0].elementos = modelo.elementos;
        if (!modelo.paginas[0].background) {
            modelo.paginas[0].background = modelo.background;
        }
    }
    
    // Garantir que verticalAlign tenha valor padrão em todos os elementos de texto
    paginas.forEach(pagina => {
        if (pagina.elementos && Array.isArray(pagina.elementos)) {
            pagina.elementos.forEach(elemento => {
                if (elemento.type === 'text' && !elemento.verticalAlign) {
                    elemento.verticalAlign = 'middle';
                }
            });
        }
    });
    
    elementosPaginaAtual = paginas[paginaAtualIndex]?.elementos || [];
}

// Configurar event listeners
function setupEventListeners() {
    document.getElementById('processarLista').addEventListener('click', processarListaAlunos);
    
    document.getElementById('gerarCertificados').addEventListener('click', function() {
        if (alunosProcessados.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "Atenção",   
                text: `Nenhum aluno para processar.`,
                confirmButtonText: "OK",
                confirmButtonColor: "green",
                theme: `${temaAlerta}`,
            })
            return;
        }
        
        document.getElementById('visualizacao-tab').click();
        mostrarAluno(0);
    });
    
    document.getElementById('paginaAnterior').addEventListener('click', function() {
        if (paginaAtualIndex > 0) {
            paginaAtualIndex--;
            elementosPaginaAtual = paginas[paginaAtualIndex]?.elementos || [];
            render(alunoAtual);
            atualizarIndicadorPagina();
        }
    });

    document.getElementById('proximaPagina').addEventListener('click', function() {
        if (paginaAtualIndex < paginas.length - 1) {
            paginaAtualIndex++;
            elementosPaginaAtual = paginas[paginaAtualIndex]?.elementos || [];
            render(alunoAtual);
            atualizarIndicadorPagina();
        }
    });
    
    document.getElementById('baixarIndividual').addEventListener('click', function() {
        if (!alunoAtual) return;
        gerarPDF(alunoAtual, true);
    });
    
    document.getElementById('baixarTodos').addEventListener('click', baixarTodosCertificados);
}

// Atualizar indicador de página
function atualizarIndicadorPagina() {
    document.getElementById('paginaAtual').textContent = `Página ${paginaAtualIndex + 1} de ${paginas.length}`;
}

// Função para mostrar aluno específico na visualização
async function mostrarAluno(index) {
    if (index < 0 || index >= alunosProcessados.length) return;

    alunoAtual = alunosProcessados[index];
    document.getElementById('alunoSelecionado').classList.remove('d-none');
    document.getElementById('alunoSelecionado').classList.add('d-flex');
    document.getElementById('alunoSelecionado').innerHTML = `
        <span><strong>Visualizando:</strong> ${index + 1} de ${alunosProcessados.length}</span>
        <div>
            <button class="btn btn-sm btn-outline-secondary" onclick="mostrarAluno(${index - 1})" ${index === 0 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left"></i> Anterior
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="mostrarAluno(${index + 1})" ${index === alunosProcessados.length - 1 ? 'disabled' : ''}>
                Próximo <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    `;

    document.getElementById('baixarIndividual').classList.remove('d-none');
    document.getElementById('baixarTodos').classList.remove('d-none');
    
    paginaAtualIndex = 0;
    elementosPaginaAtual = paginas[paginaAtualIndex].elementos;
    await render(alunoAtual);
    atualizarIndicadorPagina();
}

// Função para gerar PDF
async function gerarPDF(data, downloadIndividual = false) {
    const dadosCompletos = { ...valoresGlobais, ...data };
    const codigoVerificacao = Helpers.gerarCodigoVerificacao();
    dadosCompletos.qrCode = codigoVerificacao;

    const dadosCertificado = {
        codigo_verificacao: codigoVerificacao,
        id_modelo: modelo.id,
        nome_aluno: dadosCompletos.nome,
        cpf_aluno: dadosCompletos.cpf,
        curso: dadosCompletos.curso,
        carga_horaria: dadosCompletos.cargahoraria,
        data_emissao: new Date().toISOString(),
        data_validade: null,
        url_qr_code: `${baseUrlValidacao}/validar/${codigoVerificacao}`,
        campos_personalizados: dadosCompletos
    };

    const resultadoSalvamento = await salvarCertificadoNoBanco(dadosCertificado);

    if (!resultadoSalvamento.status) {
        console.error('Erro ao salvar certificado:', resultadoSalvamento.mensagem);
        if (downloadIndividual) {
             Swal.fire({
                icon: "warning",
                title: "Atenção",   
                text: `Erro ao salvar certificado no banco de dados: ${resultadoSalvamento.mensagem}`,
                confirmButtonText: "OK",
                confirmButtonColor: "green",
                theme: `${temaAlerta}`,
            })
            return;
        }
    }

    const TARGET_DPI = 200;
    const JPEG_QUALITY = 0.7;
    
    const pdf = new jsPDF({
        orientation: modelo.orientacao || 'portrait',
        unit: 'mm',
        format: 'a4',
        compress: true,
        compression: 'HIGH'
    });

    for (let pageIndex = 0; pageIndex < paginas.length; pageIndex++) {
        const pagina = paginas[pageIndex];
        const elementos = pagina.elementos || [];
        const paginaBackground = pagina.background || modelo.background;
        
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');
        
        tempCanvas.width = Math.round(mmW / 25.4 * TARGET_DPI);
        tempCanvas.height = Math.round(mmH / 25.4 * TARGET_DPI);
        tempCtx.imageSmoothingEnabled = true;
        tempCtx.imageSmoothingQuality = 'high';
        
        tempCtx.fillStyle = '#fff';
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        
        const scaleFactor = TARGET_DPI / 120;

        if (paginaBackground) {
            await new Promise((resolve) => {
                const bgImg = new Image();
                bgImg.crossOrigin = "Anonymous";
                bgImg.onload = function() {
                    tempCtx.drawImage(bgImg, 0, 0, tempCanvas.width, tempCanvas.height);
                    resolve();
                };
                bgImg.onerror = function() {
                    console.error('Erro ao carregar background:', paginaBackground);
                    resolve();
                };
                bgImg.src = paginaBackground;
            });
        }

        const imagePromises = [];

        for (const el of elementos) {
            tempCtx.save();
            tempCtx.globalAlpha = el.opacity ?? 1;

            if (el.rotate) {
                const centerX = (el.x * scaleFactor) + (el.w * scaleFactor) / 2;
                const centerY = (el.y * scaleFactor) + (el.h * scaleFactor) / 2;
                tempCtx.translate(centerX, centerY);
                tempCtx.rotate(el.rotate * Math.PI / 180);
                tempCtx.translate(-centerX, -centerY);
            }

            if (el.type === 'text') {
                renderFormattedText(tempCtx, el, scaleFactor, dadosCompletos);
                
            } else if (el.type === 'image' && el.src) {
                const imgPromise = new Promise((imgResolve) => {
                    const img = new Image();
                    img.crossOrigin = "Anonymous";
                    img.onload = function() {
                        tempCtx.drawImage(img,
                            el.x * scaleFactor,
                            el.y * scaleFactor,
                            el.w * scaleFactor,
                            el.h * scaleFactor);
                        imgResolve();
                    };
                    img.onerror = function() {
                        console.error('Erro ao carregar imagem:', el.src);
                        imgResolve();
                    };
                    img.src = el.src;
                });
                imagePromises.push(imgPromise);
                
            } else if (el.type === 'qrcode' && el.text) {
                let qrText = el.text;
                
                if (dadosCompletos) {
                    qrText = qrText.replace(/\{([^}]+)\}/gi, (match, tag) => {
                        const tagLower = tag.toLowerCase();
                        for (const key in dadosCompletos) {
                            if (key.toLowerCase() === tagLower) {
                                return dadosCompletos[key] || '';
                            }
                        }
                        return match;
                    });
                }
                
                try {
                    const typeNumber = 0;
                    const errorCorrectionLevel = 'H';
                    const qr = qrcode(typeNumber, errorCorrectionLevel);
                    qr.addData(qrText);
                    qr.make();
                    
                    const cellSize = Math.min(
                        (el.w * scaleFactor) / qr.getModuleCount(),
                        (el.h * scaleFactor) / qr.getModuleCount()
                    );
                    
                    const offsetX = (el.x * scaleFactor) + ((el.w * scaleFactor) - (qr.getModuleCount() * cellSize)) / 2;
                    const offsetY = (el.y * scaleFactor) + ((el.h * scaleFactor) - (qr.getModuleCount() * cellSize)) / 2;
                    
                    tempCtx.fillStyle = el.color || '#000000';
                    
                    for (let row = 0; row < qr.getModuleCount(); row++) {
                        for (let col = 0; col < qr.getModuleCount(); col++) {
                            if (qr.isDark(row, col)) {
                                tempCtx.fillRect(
                                    offsetX + col * cellSize,
                                    offsetY + row * cellSize,
                                    cellSize,
                                    cellSize
                                );
                            }
                        }
                    }
                } catch (error) {
                    console.error('Erro ao gerar QR code:', error);
                }
            }
            tempCtx.restore();
        }

        await Promise.all(imagePromises);
        
        if (pageIndex > 0) {
            pdf.addPage();
        }
        
        const imgData = tempCanvas.toDataURL('image/jpeg', JPEG_QUALITY);
        pdf.addImage(imgData, 'JPEG', 0, 0, mmW, mmH, undefined, 'FAST');
    }

    if (downloadIndividual) {
        const fileName = `certificado_${data.nome.replace(/\s+/g, '_').substring(0, 30)}.pdf`;
        pdf.save(fileName);
    } else {
        return pdf;
    }
}

// Baixar todos os certificados
async function baixarTodosCertificados() {
    if (alunosProcessados.length === 0) return;

    const zip = new JSZip();

    for (let i = 0; i < alunosProcessados.length; i++) {
        const aluno = alunosProcessados[i];
        
        await new Promise(resolve => setTimeout(resolve, 100));
        
        const pdf = await gerarPDF(aluno, false);
        const pdfData = pdf.output('blob');
        
        zip.file(`certificado_${aluno.nome.replace(/\s+/g, '_')}.pdf`, pdfData);
    }

    zip.generateAsync({ type: 'blob' }).then(function(content) {
        saveAs(content, 'certificados.zip');
    });
}

// Função para gerar instruções dinâmicas
function gerarInstrucoesDinamicas() {
    const camposAluno = determinarCamposNecessarios();
    const container = document.getElementById('instrucoesDinamicas');
    
    let html = '';
    html += `<p>Configure os valores globais acima e insira abaixo apenas os dados que variam por aluno:</p>`;
    
    if (camposAluno.length > 0) {
        html += `<p class="mb-1"><strong>Formato para dados dos contemplados:</strong></p>`;
        
        const camposFormatados = camposAluno.map(campo => {
            if (campo === 'Nome') return 'Nome';
            if (campo === 'CPF') return 'CPF';
            if (campo === 'Curso') return 'Curso';
            if (campo === 'Carga Horária') return 'Carga Horária';
            return campo.replace(/{|}/g, '');
        });
        
        html += `<p class="mb-1"><code>${camposFormatados.join(' (separador, ex: |, ;) ')}</code></p>`;
    } else {
        html += `<p class="mb-1"><code>Nome do Aluno | CPF</code></p>`;
    }
    
    const tagsEspecificasEncontradas = TAGS_ESPECIFICAS.filter(tag => 
        Array.from(placeholdersEncontradas).some(ph => ph.toLowerCase().includes(tag.toLowerCase()))
    );
    
    if (tagsEspecificasEncontradas.length > 0) {
        html += `<p class="mt-2 text-info small">
            <i class="bi bi-info-circle"></i> 
            Tags específicas detectadas: <strong>{${tagsEspecificasEncontradas.join('}, {')}}</strong>. 
            Estes valores serão aplicados a todos os certificados através dos campos acima.
        </p>`;
    }
    
    html += `<p class="mb-0">Exemplo de lista de alunos:</p>`;
    
    if (camposAluno.length > 0) {
        const exemplo1 = camposAluno.map(campo => {
            if (campo === 'Nome') return 'João Silva';
            if (campo === 'CPF') return '123.456.789-00';
            if (campo === 'Curso') return 'Programação Web';
            if (campo === 'Carga Horária') return '40h';
            return 'Valor Exemplo';
        }).join(' | ');
        
        const exemplo2 = camposAluno.map(campo => {
            if (campo === 'Nome') return 'Maria Santos';
            if (campo === 'CPF') return '987.654.321-00';
            if (campo === 'Curso') return 'Design Gráfico';
            if (campo === 'Carga Horária') return '60h';
            return 'Outro Valor';
        }).join(' | ');
        
        html += `<p class="mb-1"><code>${exemplo1}</code></p>`;
        html += `<p class="mb-0"><code>${exemplo2}</code></p>`;
    } else {
        html += `<p class="mb-1"><code>João Silva | 123.456.789-00</code></p>`;
        html += `<p class="mb-0"><code>Maria Santos | 987.654.321-00</code></p>`;
    }
    
    container.innerHTML = html;
}

// Função para atualizar a exibição das placeholders
function atualizarExibicaoPlaceholders() {
    const container = document.getElementById('placeholdersModelo');
    if (placeholdersEncontradas.size > 0) {
        let html = '<div class="d-flex flex-wrap gap-2">';
        placeholdersEncontradas.forEach(placeholder => {
            html += `<span class="badge bg-primary cursor-pointer" onclick="inserirTagNoEditor('${placeholder}')">${placeholder}</span>`;
        });
        html += '</div>';
        container.innerHTML = html;
    } else {
        container.innerHTML = '<p class="text-muted">Nenhuma placeholder encontrada no modelo.</p>';
    }
    
    gerarInstrucoesDinamicas();
}

// Adicionar CSS para os badges clicáveis
const style = document.createElement('style');
style.textContent = `
    .cursor-pointer { cursor: pointer; }
    .cursor-pointer:hover { opacity: 0.8; }
    .ql-editor {
        min-height: 150px;
        font-size: 14px;
    }
    .ql-toolbar.ql-snow {
        border: 1px solid #ccc;
        border-bottom: none;
    }
    .ql-container.ql-snow {
        border: 1px solid #ccc;
        border-top: none;
    }
`;
document.head.appendChild(style);

// Exportar a função para uso global
window.inserirTagNoEditor = inserirTagNoEditor;
window.mostrarAluno = mostrarAluno;