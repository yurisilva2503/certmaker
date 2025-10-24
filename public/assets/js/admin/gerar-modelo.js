const A4_MM = { w: 210, h: 297 };
const BASE_DPI = 120;
const EXPORT_DPI = 200;

function mmToPx(mm, dpi = BASE_DPI) {
  return Math.round((mm / 25.4) * dpi);
}

function pxToMm(px, dpi = BASE_DPI) {
  return px / (dpi / 25.4);
}

const canvas = document.getElementById("stage");
const ctx = canvas.getContext("2d");

let pages = [];
let currentPageIndex = 0;
let elements = [];
let selectedId = null;
let backgroundImage = null;
let currentZoom = 0.65;
let orientation = "landscape";
let backgroundImageData = null;
let quill = null;

const uid = () => Math.random().toString(36).slice(2, 9);

const addTextBtn = document.getElementById("addTextBtn");
const uploadImageBtn = document.getElementById("uploadImageBtn");
const layersList = document.getElementById("layersList");
const propsPanel = document.getElementById("elementProps");
const noSelection = document.getElementById("noSelection");
const propType = document.getElementById("propType");
const propX = document.getElementById("propX");
const propY = document.getElementById("propY");
const propW = document.getElementById("propW");
const propH = document.getElementById("propH");
const propText = document.getElementById("propText");
const propFont = document.getElementById("propFont");
const propFontSize = document.getElementById("propFontSize");
const propColor = document.getElementById("propColor");
const propOpacity = document.getElementById("propOpacity");
const propAlign = document.getElementById("propAlign");
const propRotate = document.getElementById("propRotate");
const bringFront = document.getElementById("bringFront");
const sendBack = document.getElementById("sendBack");
const deleteEl = document.getElementById("deleteEl");
const backgroundInput = document.getElementById("backgroundInput");
const orientationSelect = document.getElementById("orientationSelect");
const zoomInput = document.getElementById("zoom");
const modelName = document.getElementById("modelName");
const saveModelBtn = document.getElementById("saveModelBtn");
const clearBackgroundBtn = document.getElementById("clearBackgroundBtn");
const addQRCodeBtn = document.getElementById("addQRCodeBtn");
const addPageBtn = document.getElementById("addPageBtn");
const prevPageBtn = document.getElementById("prevPageBtn");
const nextPageBtn = document.getElementById("nextPageBtn");
const pageIndicator = document.getElementById("pageIndicator");
const addCustomTagBtn = document.getElementById("addCustomTagBtn");
const customTagInput = document.getElementById("customTagInput");
const removePageBtn = document.getElementById("removePageBtn");
const addLinkValidacao = document.getElementById("addLinkValidacao");
const copyElBtn = document.getElementById("copyEl");
const propVerticalAlign = document.getElementById("propVerticalAlign");

(async function () {
    initQuillEditor();
    function initQuillEditor() {
    const toolbarOptions = [
        ['bold', 'italic', 'underline'],
        
    ];

    quill = new Quill('#editor-container', {
        modules: {
            toolbar: toolbarOptions
        },
        theme: 'snow',
        formats: ['bold', 'italic', 'underline', 'list'] 
    });

    quill.on('text-change', function() {
        const htmlContent = quill.root.innerHTML;
        propText.value = htmlContent;
        
        const el = elements.find((x) => x.id === selectedId);
        if (el && el.type === "text") {
            el.text = htmlContent;
            render();
        }
    });

    document.querySelectorAll('[data-format]').forEach(button => {
        button.addEventListener('click', function() {
            const format = this.getAttribute('data-format');
            const range = quill.getSelection();
            if (range) {
                if (format === 'clean') {
                    quill.removeFormat(range.index, range.length);
                } else {
                    quill.format(format, !quill.getFormat(range.index, range.length)[format]);
                }
            } else {
                const index = quill.getSelection(true).index;
                quill.formatLine(index, 1, format, !quill.getFormat(index, 1)[format]);
            }
        });
    });
}


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
                
                if (tagName === 'strong' || tagName === 'b') {
                    return currentLine + `<strong>${elementContent}</strong>`;
                } else if (tagName === 'em' || tagName === 'i') {
                    return currentLine + `<em>${elementContent}</em>`;
                } else if (tagName === 'u') {
                    return currentLine + `<u>${elementContent}</u>`;
                } else if (tagName === 'span') {
                    const style = node.getAttribute('style') || '';
                    return currentLine + `<span style="${style}">${elementContent}</span>`;
                } else {
                    return currentLine + elementContent;
                }
            }
        }
        return currentLine;
    }
    
    Array.from(doc.body.childNodes).forEach(node => {
        processNode(node, '');
    });
    
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

function measureFormattedText(line, el, ctx) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(line, 'text/html');
    const nodes = doc.body.childNodes;
    let totalWidth = 0;

    for (const node of nodes) {
        if (node.nodeType === Node.TEXT_NODE) {
            ctx.font = `${el.fontWeight === "bold" ? "bold" : "normal"} ${el.fontSize}px ${el.font || "Helvetica"}`;
            totalWidth += ctx.measureText(node.textContent).width;
        } else if (node.nodeType === Node.ELEMENT_NODE) {
            let fontWeight = el.fontWeight === "bold" ? "bold" : "normal";
            let fontStyle = "normal";
            let fontFamily = el.font || "Helvetica";

            const style = node.style;
            const tagName = node.tagName.toLowerCase();
            
            if (tagName === 'strong' || tagName === 'b' || style.fontWeight === 'bold') {
                fontWeight = 'bold';
            }
            if (tagName === 'em' || tagName === 'i' || style.fontStyle === 'italic') {
                fontStyle = 'italic';
            }
            if (style.fontFamily) {
                fontFamily = style.fontFamily;
            }

            ctx.font = `${fontWeight} ${fontStyle} ${el.fontSize}px ${fontFamily}`;
            totalWidth += ctx.measureText(node.textContent).width;
        }
    }

    return totalWidth;
}


function renderFormattedLine(ctx, line, x, y, el) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(line, 'text/html');
    const container = doc.body;
    
    ctx.textBaseline = "alphabetic";
    ctx.textAlign = "left";
    
    let totalWidth = 0;
    const textSegments = [];
    
    function processNode(node, currentStyles = {}) {
        const styles = { ...currentStyles };
        
        if (node.nodeType === Node.ELEMENT_NODE) {
            const tagName = node.tagName.toLowerCase();
            const style = node.style;
            
            if (tagName === 'strong' || tagName === 'b' || style.fontWeight === 'bold') {
                styles.fontWeight = 'bold';
            }

            if (tagName === 'em' || tagName === 'i' || style.fontStyle === 'italic') {
                styles.fontStyle = 'italic';
            }

            if (tagName === 'u' || style.textDecoration === 'underline') {
                styles.textDecoration = 'underline';
            }
            
            if (style.color) {
                styles.color = style.color;
            }
            
            Array.from(node.childNodes).forEach(child => {
                processNode(child, styles);
            });
            
        } else if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent;
            if (!text.trim()) return;
            
            const fontWeight = styles.fontWeight || (el.fontWeight === "bold" ? "bold" : "normal");
            const fontStyle = styles.fontStyle || "normal";
            const fontSize = el.fontSize;
            const fontFamily = el.font || "Helvetica";
            
            const fontString = `${fontWeight} ${fontStyle} ${fontSize}px ${fontFamily}`;
            
            ctx.font = fontString;
            const width = ctx.measureText(text).width;
            totalWidth += width;
            
            textSegments.push({
                content: text,
                width,
                font: fontString,
                color: styles.color || el.color || "#222",
                textDecoration: styles.textDecoration || 'none',
                fontWeight: fontWeight,
                fontStyle: fontStyle
            });
        }
    }
    
    Array.from(container.childNodes).forEach(node => {
        processNode(node, {});
    });
    
    let startX = x;
    if (el.align === "center") {
        startX = x - totalWidth / 2;
    } else if (el.align === "right") {
        startX = x - totalWidth;
    }
    
    let currentX = startX;
    
    for (const segment of textSegments) {
        ctx.font = segment.font;
        ctx.fillStyle = segment.color;
        
        ctx.fillText(segment.content, currentX, y);
        
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

function getStyledTextWidth(node, el, ctx) {
    const { fontWeight, fontStyle, fontFamily } = getNodeStyles(node, el);
    ctx.font = `${fontWeight} ${fontStyle} ${el.fontSize}px ${fontFamily}`;
    return ctx.measureText(node.textContent).width;
}

function getNodeStyles(node, el) {
    let fontWeight = el.fontWeight === "bold" ? "bold" : "normal";
    let fontStyle = "normal";
    let fontFamily = el.font || "Helvetica";
    
    const style = node.style;
    const tagName = node.tagName.toLowerCase();
    
    if (tagName === 'strong' || tagName === 'b' || style.fontWeight === 'bold') {
        fontWeight = 'bold';
    }
    if (tagName === 'em' || tagName === 'i' || style.fontStyle === 'italic') {
        fontStyle = 'italic';
    }
    if (style.fontFamily) {
        fontFamily = style.fontFamily;
    }
    
    return { fontWeight, fontStyle, fontFamily };
}

function renderStyledNode(ctx, node, x, y, el) {
    const { fontWeight, fontStyle, fontFamily } = getNodeStyles(node, el);
    const textColor = node.style.color || el.color || "#222";
    
    ctx.font = `${fontWeight} ${fontStyle} ${el.fontSize}px ${fontFamily}`;
    ctx.fillStyle = textColor;
    ctx.textAlign = "left";
    ctx.fillText(node.textContent, x, y);
    
    if (node.tagName.toLowerCase() === 'u' || node.style.textDecoration === 'underline') {
        const textWidth = ctx.measureText(node.textContent).width;
        ctx.strokeStyle = textColor;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x, y + 2);
        ctx.lineTo(x + textWidth, y + 2);
        ctx.stroke();
    }
}


function renderFormattedTextHighRes(ctx, el, scaleFactor) {
    const scaledFontSize = el.fontSize * scaleFactor;
    const lines = extractTextLines(el.text || "");
    const lineHeight = scaledFontSize * 1.2;
    
    let startY;
    const totalTextHeight = lines.length * lineHeight;
    
    switch (el.verticalAlign || "middle") {
        case "top":
            startY = el.y * scaleFactor + lineHeight;
            break;
        case "bottom":
            startY = el.y * scaleFactor + el.h * scaleFactor - totalTextHeight + lineHeight;
            break;
        case "middle":
        default:
            startY = el.y * scaleFactor + (el.h * scaleFactor - totalTextHeight) / 2 + lineHeight;
            break;
    }

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (!line) continue;
        
        const x = getTextXPosition(el, scaleFactor);
        renderFormattedLineHighRes(ctx, line, x, startY + (i * lineHeight), el, scaledFontSize, scaleFactor);
    }
}

function renderFormattedLineHighRes(ctx, line, x, y, el, fontSize, scaleFactor) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(line, 'text/html');
    const nodes = doc.body.childNodes;
    
    ctx.textBaseline = "alphabetic";
    
    let totalWidth = 0;
    const segments = [];
    
    function processNodes(nodes, currentStyles) {
        for (const node of nodes) {
            if (node.nodeType === Node.TEXT_NODE) {
                const text = node.textContent;
                if (text.trim() === '') continue;
                
                ctx.font = `${currentStyles.fontWeight} ${currentStyles.fontStyle} ${fontSize}px ${currentStyles.fontFamily}`;
                const width = ctx.measureText(text).width;
                totalWidth += width;
                segments.push({
                    type: 'text',
                    content: text,
                    width,
                    styles: { ...currentStyles }
                });
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                const nodeStyles = getNodeStyles(node, el);
                const newStyles = {
                    fontWeight: nodeStyles.fontWeight !== 'normal' ? nodeStyles.fontWeight : currentStyles.fontWeight,
                    fontStyle: nodeStyles.fontStyle !== 'normal' ? nodeStyles.fontStyle : currentStyles.fontStyle,
                    textDecoration: nodeStyles.textDecoration !== 'none' ? nodeStyles.textDecoration : currentStyles.textDecoration,
                    fontFamily: nodeStyles.fontFamily !== 'Helvetica' ? nodeStyles.fontFamily : currentStyles.fontFamily
                };
                
                processNodes(node.childNodes, newStyles);
            }
        }
    }
    
    const initialStyles = {
        fontWeight: el.fontWeight === "bold" ? "bold" : "normal",
        fontStyle: "normal",
        textDecoration: "none",
        fontFamily: el.font || "Helvetica"
    };
    
    processNodes(nodes, initialStyles);
    
    let startX = x;
    if (el.align === "center") {
        startX = x - totalWidth / 2;
    } else if (el.align === "right") {
        startX = x - totalWidth;
    }
    
    let currentX = startX;
    
    for (const segment of segments) {
        const { fontWeight, fontStyle, textDecoration, fontFamily } = segment.styles;
        ctx.font = `${fontWeight} ${fontStyle} ${fontSize}px ${fontFamily}`;
        ctx.fillStyle = el.color || "#222";
        ctx.textAlign = "left";
        ctx.fillText(segment.content, currentX, y);
        
        if (textDecoration === "underline") {
            ctx.strokeStyle = el.color || "#222";
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(currentX, y + 2);
            ctx.lineTo(currentX + segment.width, y + 2);
            ctx.stroke();
        }
        
        currentX += segment.width;
    }
}


function renderFormattedTextHighRes(ctx, el, scaleFactor) {
    const scaledFontSize = el.fontSize * scaleFactor;
    const lines = extractTextLines(el.text || "");
    const lineHeight = scaledFontSize * 1.2;
    
    let startY;
    switch (el.verticalAlign || "middle") {
        case "top":
            startY = el.y * scaleFactor + lineHeight;
            break;
        case "bottom":
            startY = el.y * scaleFactor + el.h * scaleFactor - (lines.length - 1) * lineHeight;
            break;
        case "middle":
        default:
            startY = el.y * scaleFactor + (el.h * scaleFactor) / 2 - ((lines.length - 1) / 2) * lineHeight;
            break;
    }

    for (const line of lines) {
        let x = getTextXPosition(el, scaleFactor);
        renderFormattedLineHighRes(ctx, line, x, startY, el, scaledFontSize, scaleFactor);
        startY += lineHeight;
    }
}

propVerticalAlign.addEventListener("change", () => {
    const el = elements.find((x) => x.id === selectedId);
    if (!el || el.type !== "text") return;
    
    el.verticalAlign = propVerticalAlign.value;
    render();
    rebuildLayersUI();
});

function renderFormattedText(ctx, el) {
    const lines = extractTextLines(el.text || "");
    const lineHeight = el.fontSize * 1.2;
    
    let startY;
    const totalTextHeight = lines.length * lineHeight;
    
    switch (el.verticalAlign || "middle") {
        case "top":
            startY = el.y + lineHeight;
            break;
        case "bottom":
            startY = el.y + el.h - totalTextHeight + lineHeight;
            break;
        case "middle":
        default:
            startY = el.y + (el.h - totalTextHeight) / 2 + lineHeight;
            break;
    }

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (!line) continue;
        
        const x = getTextXPosition(el, 1);
        renderFormattedLine(ctx, line, x, startY + (i * lineHeight), el);
    }
}
    addNewPage();
    setCanvasForOrientation("landscape");

    function addNewPage() {
        const newPage = {
            elements: [],
            background: null,
            backgroundData: null,
        };
        pages.push(newPage);
        currentPageIndex = pages.length - 1;
        elements = pages[currentPageIndex].elements;
        backgroundImage = pages[currentPageIndex].background;
        backgroundImageData = pages[currentPageIndex].backgroundData;
        updatePageIndicator();
        render();
        rebuildLayersUI();
    }

    function removeCurrentPage() {
        if (pages.length <= 1) {
            Swal.fire({
                icon: "warning",
                title: "Atenção",
                text: "O modelo deve ter pelo menos uma página.",
                confirmButtonText: "OK",
                confirmButtonColor: "",
                theme: `${temaAlerta}`,
            })
            return;
        }

        pages.splice(currentPageIndex, 1);
        if (currentPageIndex >= pages.length) {
            currentPageIndex = pages.length - 1;
        }

        elements = pages[currentPageIndex].elements;
        backgroundImage = pages[currentPageIndex].background;
        backgroundImageData = pages[currentPageIndex].backgroundData;

        updatePageIndicator();
        render();
        rebuildLayersUI();
    }

    function switchPage(index) {
        if (index >= 0 && index < pages.length) {
            currentPageIndex = index;
            elements = pages[currentPageIndex].elements;
            backgroundImage = pages[currentPageIndex].background;
            backgroundImageData = pages[currentPageIndex].backgroundData;
            updatePageIndicator();
            render();
            rebuildLayersUI();
            populateProps();
        }
    }

    function updatePageIndicator() {
        pageIndicator.textContent = `Página ${currentPageIndex + 1}/${pages.length}`;
    }

    removePageBtn.addEventListener("click", removeCurrentPage);

    addLinkValidacao.addEventListener("click", function () {
        const id = uid();
        const el = {
            id,
            type: "text",
            x: canvas.width * 0.1,
            y: canvas.height * 0.4,
            w: canvas.width * 0.8,
            h: 60,
            text: `<p>Este certificado pode ser validado em: ${baseUrlValidacao}/validar/{qrCode}</p>`,
            font: "helvetica",
            fontSize: 20,
            fontWeight: "normal",
            color: "#222222",
            align: "center",
            verticalAlign: "middle",
            opacity: 1,
            rotate: 0,
        };
        elements.push(el);
        selectElement(id);
        render();
    });

    addPageBtn.addEventListener("click", () => {
        addNewPage();
    });

    prevPageBtn.addEventListener("click", () => {
        if (currentPageIndex > 0) {
            switchPage(currentPageIndex - 1);
        }
    });

    nextPageBtn.addEventListener("click", () => {
        if (currentPageIndex < pages.length - 1) {
            switchPage(currentPageIndex + 1);
        }
    });

    function setCanvasForOrientation(orient) {
        orientation = orient;
        const mmW = orient === "portrait" ? A4_MM.w : A4_MM.h;
        const mmH = orient === "portrait" ? A4_MM.h : A4_MM.w;

        canvas.width = mmToPx(mmW, BASE_DPI);
        canvas.height = mmToPx(mmH, BASE_DPI);

        canvas.style.width = canvas.width * currentZoom + "px";
        canvas.style.height = canvas.height * currentZoom + "px";
        render();
    }

    function createHighResCanvas() {
        const mmW = orientation === "portrait" ? A4_MM.w : A4_MM.h;
        const mmH = orientation === "portrait" ? A4_MM.h : A4_MM.w;

        const tempCanvas = document.createElement("canvas");
        tempCanvas.width = mmToPx(mmW, EXPORT_DPI);
        tempCanvas.height = mmToPx(mmH, EXPORT_DPI);

        const tempCtx = tempCanvas.getContext("2d");
        tempCtx.imageSmoothingEnabled = true;
        tempCtx.imageSmoothingQuality = "high";

        return { tempCanvas, tempCtx };
    }

    function renderHighResolution() {
        const { tempCanvas, tempCtx } = createHighResCanvas();

        tempCtx.fillStyle = "#ffffff";
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

        if (backgroundImage) {
            tempCtx.save();
            tempCtx.globalAlpha = 1;
            tempCtx.drawImage(backgroundImage, 0, 0, tempCanvas.width, tempCanvas.height);
            tempCtx.restore();
        }

        const scaleFactor = EXPORT_DPI / BASE_DPI;

        for (const el of elements) {
            tempCtx.save();
            tempCtx.globalAlpha = el.opacity ?? 1;

            if (el.rotate) {
                tempCtx.translate(
                    el.x * scaleFactor + (el.w * scaleFactor) / 2,
                    el.y * scaleFactor + (el.h * scaleFactor) / 2
                );
                tempCtx.rotate((el.rotate * Math.PI) / 180);
                tempCtx.translate(
                    -(el.x * scaleFactor + (el.w * scaleFactor) / 2),
                    -(el.y * scaleFactor + (el.h * scaleFactor) / 2)
                );
            }

            if (el.type === "image") {
                if (el.imgObj) {
                    tempCtx.drawImage(
                        el.imgObj,
                        el.x * scaleFactor,
                        el.y * scaleFactor,
                        el.w * scaleFactor,
                        el.h * scaleFactor
                    );
                }
            } else if (el.type === "text") {
                renderFormattedTextHighRes(tempCtx, el, scaleFactor);
            } else if (el.type === "qrcode") {
                const qrSize = Math.min(el.w * scaleFactor, el.h * scaleFactor);
                const qrX = el.x * scaleFactor + (el.w * scaleFactor - qrSize) / 2;
                const qrY = el.y * scaleFactor + (el.h * scaleFactor - qrSize) / 2;

                tempCtx.fillStyle = "#000000";
                tempCtx.fillRect(qrX, qrY, qrSize, qrSize);
            }

            tempCtx.restore();
        }

        return tempCanvas;
    }

    zoomInput.addEventListener("input", (e) => {
        currentZoom = Number(e.target.value);
        canvas.style.width = canvas.width * currentZoom + "px";
        canvas.style.height = canvas.height * currentZoom + "px";
    });

    addTextBtn.addEventListener("click", () => {
        const id = uid();
        const el = {
            id,
            type: "text",
            x: canvas.width * 0.1,
            y: canvas.height * 0.4,
            w: canvas.width * 0.8,
            h: 60,
            text: "<p><strong>{nome}</strong></p><p>Concluiu o curso <em>{curso}</em></p>",
            font: "helvetica",
            fontSize: 20,
            fontWeight: "normal",
            color: "#222222",
            align: "center",
            verticalAlign: "middle", 
            opacity: 1,
            rotate: 0,
        };
        elements.push(el);
        selectElement(id);
        render();
    });

    addQRCodeBtn.addEventListener("click", () => {
        const id = uid();
        const size = Math.min(canvas.width, canvas.height) * 0.2;
        const el = {
            id,
            type: "qrcode",
            x: canvas.width * 0.4,
            y: canvas.height * 0.7,
            w: size,
            h: size,
            text: `${baseUrlValidacao}/validar/{qrCode}`,
            opacity: 1,
            rotate: 0,
        };
        elements.push(el);
        selectElement(id);
        render();
    });

    uploadImageBtn.addEventListener("change", async (ev) => {
        const file = ev.target.files[0];
        if (!file) return;
        const dataUrl = await readFileAsDataURL(file);
        const img = await loadImage(dataUrl);
        const id = uid();
        const ratio = img.width / img.height;
        const w = canvas.width * 0.5;
        const h = w / ratio;
        const el = {
            id,
            type: "image",
            x: canvas.width * 0.25,
            y: canvas.height * 0.25,
            w,
            h,
            imgObj: img,
            opacity: 1,
            rotate: 0,
            src: dataUrl,
        };
        elements.push(el);
        selectElement(id);
        render();
        ev.target.value = "";
    });

    backgroundInput.addEventListener("change", async (ev) => {
        const f = ev.target.files[0];
        if (!f) return;
        const url = await readFileAsDataURL(f);
        backgroundImage = await loadImage(url);
        backgroundImageData = url;

        pages[currentPageIndex].background = backgroundImage;
        pages[currentPageIndex].backgroundData = backgroundImageData;

        render();
        ev.target.value = "";
        clearBackgroundBtn.style.display = "block";
    });

    clearBackgroundBtn.addEventListener("click", () => {
        backgroundImage = null;
        backgroundImageData = null;

        pages[currentPageIndex].background = null;
        pages[currentPageIndex].backgroundData = null;

        render();
        clearBackgroundBtn.style.display = "none";
    });

    addCustomTagBtn.addEventListener("click", () => {
        const customTag = customTagInput.value.trim();
        if (!customTag) return;

        let formattedTag = customTag;
        if (!formattedTag.startsWith("{")) formattedTag = "{" + formattedTag;
        if (!formattedTag.endsWith("}")) formattedTag = formattedTag + "}";

        if (!selectedId) {
            Swal.fire({
                icon: "error",
                title: "Erro!",
                text: "Por favor, selecione um elemento de texto primeiro.",
                confirmButtonColor: "green",
                confirmButtonText: "Ok",
                theme: `${temaAlerta}`,
            });
            return;
        }

        const el = elements.find((x) => x.id === selectedId);
        if (!el || el.type !== "text") {
            Swal.fire({
                icon: "error",
                title: "Erro!",
                text: "O elemento selecionado não é um texto.",
                confirmButtonColor: "green",
                confirmButtonText: "Ok",
                theme: `${temaAlerta}`,
            });
            return;
        }

        const range = quill.getSelection();
        if (range) {
            quill.insertText(range.index, ` ${formattedTag}`);
        } else {
            quill.insertText(quill.getLength(), ` ${formattedTag}`);
        }
        customTagInput.value = "";
    });

    function rebuildLayersUI() {
        layersList.innerHTML = "";
        for (let i = elements.length - 1; i >= 0; i--) {
            const el = elements[i];
            const div = document.createElement("div");
            div.className = "layer-item" + (el.id === selectedId ? " selected" : "");

            let displayText = el.type.toUpperCase();
            if (el.type === "text") {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = el.text || "";
                const plainText = tempDiv.textContent || tempDiv.innerText || "";
                displayText += " - " + plainText.substring(0, 20) + (plainText.length > 20 ? "..." : "");
            } else if (el.type === "qrcode") {
                displayText += " - QR Code";
            }

            div.innerHTML = `<span>${displayText}</span>
                            <div>
                              <button data-id="${el.id}" class="small selBtn btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                            </div>`;
            layersList.appendChild(div);
        }

        layersList.querySelectorAll(".selBtn").forEach((b) => {
            b.addEventListener("click", () => selectElement(b.dataset.id));
        });
    }

    function selectElement(id) {
        selectedId = id;
        rebuildLayersUI();
        populateProps();
    }

    function populateProps() {
        const el = elements.find((x) => x.id === selectedId);
        if (!el) {
            propsPanel.style.display = "none";
            noSelection.style.display = "block";
            quill.enable(false);
            quill.setText('');
            return;
        }
        
        noSelection.style.display = "none";
        propsPanel.style.display = "block";
        propType.value = el.type;
        propX.value = Math.round(el.x);
        propY.value = Math.round(el.y);
        propW.value = Math.round(el.w);
        propH.value = Math.round(el.h);
        propOpacity.value = el.opacity ?? 1;
        propRotate.value = el.rotate ?? 0;

        const textControls = document.querySelectorAll('.propriedades-texto, .propriedades-fonte, .propriedades-fonte-2, .propriedades-cor-texto');
        const qrControls = [propText];

        if (el.type === "text") {
            textControls.forEach(control => control.style.display = "block");
            qrControls.forEach(control => control.parentElement.style.display = "none");
            
            quill.enable(true);
            quill.root.innerHTML = el.text || "";
            propText.value = el.text || "";
            propText.parentElement.style.display = "block";
            propFont.value = el.font || "helvetica";
            propFontSize.value = el.fontSize || 18;
            propColor.value = el.color || "#222";
            propAlign.value = el.align || "center";
            propVerticalAlign.value = el.verticalAlign || "middle";
        } else if (el.type === "qrcode") {
            textControls.forEach(control => control.style.display = "none");
            propText.parentElement.style.display = "block";
            propText.value = el.text || "";
            quill.enable(false);
        } else {
            textControls.forEach(control => control.style.display = "none");
            qrControls.forEach(control => control.parentElement.style.display = "none");
            quill.enable(false);
        }
    }

    [propX, propY, propW, propH, propOpacity, propRotate].forEach((inp) => {
        inp.addEventListener("input", () => {
            const el = elements.find((x) => x.id === selectedId);
            if (!el) return;
            el.x = Number(propX.value);
            el.y = Number(propY.value);
            el.w = Number(propW.value);
            el.h = Number(propH.value);
            el.opacity = Number(propOpacity.value);
            el.rotate = Number(propRotate.value);
            render();
            rebuildLayersUI();
        });
    });

    [propFont, propFontSize, propColor, propAlign].forEach((inp) => {
        inp.addEventListener("input", () => {
            const el = elements.find((x) => x.id === selectedId);
            if (!el || el.type !== "text") return;

            el.font = propFont.value;
            el.fontSize = Number(propFontSize.value);
            el.color = propColor.value;
            el.align = propAlign.value;

            render();
            rebuildLayersUI();
        });
    });

    propText.addEventListener("input", () => {
        const el = elements.find((x) => x.id === selectedId);
        if (!el) return;

        if (el.type === "text") {
            el.text = propText.value;
            quill.root.innerHTML = propText.value;
        } else if (el.type === "qrcode") {
            el.text = propText.value;
        }

        render();
        rebuildLayersUI();
    });

    bringFront.addEventListener("click", () => {
        const idx = elements.findIndex((x) => x.id === selectedId);
        if (idx < 0) return;
        const [el] = elements.splice(idx, 1);
        elements.push(el);
        render();
        rebuildLayersUI();
    });
    
    sendBack.addEventListener("click", () => {
        const idx = elements.findIndex((x) => x.id === selectedId);
        if (idx < 0) return;
        const [el] = elements.splice(idx, 1);
        elements.unshift(el);
        render();
        rebuildLayersUI();
    });
    
    deleteEl.addEventListener("click", () => {
        const idx = elements.findIndex((x) => x.id === selectedId);
        if (idx < 0) return;
        elements.splice(idx, 1);
        selectedId = null;
        render();
        rebuildLayersUI();
        populateProps();
    });
    
    copyElBtn.addEventListener("click", () => {
        const idx = elements.findIndex((x) => x.id === selectedId);
        if (idx < 0) return;

        const el = elements[idx];
        const newEl = JSON.parse(JSON.stringify(el));
        newEl.id = uid();

        if (typeof newEl.x === "number") newEl.x += 20;
        if (typeof newEl.y === "number") newEl.y += 20;

        elements.push(newEl);
        selectedId = newEl.id;
        render();
        rebuildLayersUI();
        populateProps();
    });

    let dragging = null;
    let dragOffX = 0, dragOffY = 0;
    let resizing = null;
    let resizeCorner = null;
    
    canvas.addEventListener("pointerdown", (e) => {
        const rect = canvas.getBoundingClientRect();
        const x = (e.clientX - rect.left) / currentZoom;
        const y = (e.clientY - rect.top) / currentZoom;
        
        for (let i = elements.length - 1; i >= 0; i--) {
            const el = elements[i];
            if (pointInEl(x, y, el)) {
                selectElement(el.id);
                const corner = whichCorner(x, y, el);
                if (corner) {
                    resizing = el;
                    resizeCorner = corner;
                } else {
                    dragging = el;
                    dragOffX = x - el.x;
                    dragOffY = y - el.y;
                }
                return;
            }
        }
        
        selectedId = null;
        rebuildLayersUI();
        populateProps();
    });

    canvas.addEventListener("pointermove", (e) => {
        if (!dragging && !resizing) return;
        const rect = canvas.getBoundingClientRect();
        const x = (e.clientX - rect.left) / currentZoom;
        const y = (e.clientY - rect.top) / currentZoom;
        
        if (dragging) {
            dragging.x = x - dragOffX;
            dragging.y = y - dragOffY;
            populateProps();
            render();
        } else if (resizing) {
            if (resizeCorner === "br") {
                resizing.w = Math.max(20, x - resizing.x);
                resizing.h = Math.max(20, y - resizing.y);
            } else if (resizeCorner === "bl") {
                const newW = Math.max(20, resizing.w + (resizing.x - x));
                resizing.h = Math.max(20, y - resizing.y);
                resizing.x = x;
                resizing.w = newW;
            } else if (resizeCorner === "tr") {
                const newH = Math.max(20, resizing.h + (resizing.y - y));
                resizing.w = Math.max(20, x - resizing.x);
                resizing.y = y;
                resizing.h = newH;
            } else if (resizeCorner === "tl") {
                const oldX = resizing.x, oldY = resizing.y;
                const newW = Math.max(20, resizing.w + (oldX - x));
                const newH = Math.max(20, resizing.h + (oldY - y));
                resizing.x = x;
                resizing.y = y;
                resizing.w = newW;
                resizing.h = newH;
            }
            populateProps();
            render();
        }
    });

    canvas.addEventListener("pointerup", () => {
        dragging = null;
        resizing = null;
        resizeCorner = null;
    });

    function whichCorner(x, y, el) {
        const margin = 12;
        if (Math.abs(x - (el.x + el.w)) <= margin && Math.abs(y - (el.y + el.h)) <= margin) return "br";
        if (Math.abs(x - el.x) <= margin && Math.abs(y - (el.y + el.h)) <= margin) return "bl";
        if (Math.abs(x - (el.x + el.w)) <= margin && Math.abs(y - el.y) <= margin) return "tr";
        if (Math.abs(x - el.x) <= margin && Math.abs(y - el.y) <= margin) return "tl";
        return null;
    }

    function pointInEl(x, y, el) {
        return x >= el.x && x <= el.x + el.w && y >= el.y && y <= el.y + el.h;
    }

    function render() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        if (backgroundImage) {
            ctx.save();
            ctx.globalAlpha = 1;
            ctx.drawImage(backgroundImage, 0, 0, canvas.width, canvas.height);
            ctx.restore();
        }

        drawGuides(ctx, canvas, 50);

        for (const el of elements) {
            ctx.save();
            ctx.globalAlpha = el.opacity ?? 1;
            
            if (el.rotate) {
                ctx.translate(el.x + el.w / 2, el.y + el.h / 2);
                ctx.rotate((el.rotate * Math.PI) / 180);
                ctx.translate(-(el.x + el.w / 2), -(el.y + el.h / 2));
            }

            if (el.type === "image") {
                if (el.imgObj) ctx.drawImage(el.imgObj, el.x, el.y, el.w, el.h);
            } else if (el.type === "text") {
                renderFormattedText(ctx, el);
            } else if (el.type === "qrcode") {
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(el.x, el.y, el.w, el.h);
                ctx.strokeStyle = "#333333";
                ctx.lineWidth = 2;
                ctx.strokeRect(el.x, el.y, el.w, el.h);

                const size = Math.min(el.w, el.h);
                const squareSize = size * 0.2;
                ctx.fillStyle = "#000000";
                ctx.fillRect(el.x, el.y, squareSize, squareSize);
                ctx.fillRect(el.x + el.w - squareSize, el.y, squareSize, squareSize);
                ctx.fillRect(el.x, el.y + el.h - squareSize, squareSize, squareSize);

                ctx.fillStyle = "#666666";
                ctx.font = "12px Arial";
                ctx.textAlign = "center";
                ctx.fillText("QR Code", el.x + el.w / 2, el.y + el.h / 2 + 5);
            }

            if (el.id === selectedId) {
                ctx.lineWidth = 2;
                ctx.strokeStyle = "#2c98ff";
                ctx.strokeRect(el.x - 2, el.y - 2, el.w + 4, el.h + 4);
                
                const h = 8;
                const corners = [
                    [el.x, el.y],
                    [el.x + el.w, el.y],
                    [el.x, el.y + el.h],
                    [el.x + el.w, el.y + el.h],
                ];
                ctx.fillStyle = "#ffffff";
                ctx.strokeStyle = "#2c98ff";
                for (const c of corners) {
                    ctx.fillRect(c[0] - h / 2, c[1] - h / 2, h, h);
                    ctx.strokeRect(c[0] - h / 2, c[1] - h / 2, h, h);
                }
            }
            ctx.restore();
        }
    }

    function drawGuides(ctx, canvas, step = 50) {
        ctx.save();
        ctx.strokeStyle = "rgba(0,0,0,0.08)";
        ctx.lineWidth = 1;

        for (let x = 0; x < canvas.width; x += step) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, canvas.height);
            ctx.stroke();
        }

        for (let y = 0; y < canvas.height; y += step) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(canvas.width, y);
            ctx.stroke();
        }

        ctx.restore();
    }

    async function carregarModeloExistente(modeloId) {
        try {
            const response = await fetch(`/admin/certificados/obter-modelo/${modeloId}`);
            const modelo = await response.json();

            if (modelo && modelo.modelo.elementos) {
                orientation = modelo.modelo.orientacao || "landscape";
                orientationSelect.value = orientation;
                setCanvasForOrientation(orientation);

                if (modelo.modelo.nome) {
                    modelName.value = modelo.modelo.nome;
                }

                pages = [];
                addNewPage();

                let elementosModelo = [];

                try {
                    if (typeof modelo.modelo.elementos === "string") {
                        elementosModelo = JSON.parse(modelo.modelo.elementos);
                    } else {
                        elementosModelo = modelo.modelo.elementos;
                    }
                } catch (e) {
                    console.error("Erro ao parsear elementos:", e);
                    elementosModelo = [];
                }

                if (modelo.modelo.paginas) {
                    let paginasModelo = [];

                    try {
                        if (typeof modelo.paginas === "string") {
                            paginasModelo = JSON.parse(modelo.modelo.paginas);
                        } else {
                            paginasModelo = modelo.modelo.paginas;
                        }

                        if (paginasModelo.length > 0) {
                            pages = [];

                            for (let i = 0; i < paginasModelo.length; i++) {
                                const pagina = paginasModelo[i];
                                addNewPage();

                                if (pagina.background) {
                                    try {
                                        const img = await loadImage(pagina.background);
                                        pages[i].background = img;
                                        pages[i].backgroundData = pagina.background;
                                    } catch (e) {
                                        console.error("Erro ao carregar background:", e);
                                    }
                                }

                                if (pagina.elementos && Array.isArray(pagina.elementos)) {
                                    for (const el of pagina.elementos) {
                                        await processarElemento(el, i);
                                    }
                                }
                            }

                            switchPage(0);
                        }
                    } catch (e) {
                        console.error("Erro ao processar páginas:", e);
                    }
                } else {
                    for (const el of elementosModelo) {
                        if (el.type === "text" && !el.verticalAlign) {
                            el.verticalAlign = "middle";
                        }
                        await processarElemento(el, 0);
                    }

                    if (modelo.background) {
                        try {
                            const img = await loadImage(modelo.background);
                            pages[0].background = img;
                            pages[0].backgroundData = modelo.background;
                            clearBackgroundBtn.style.display = "block";
                        } catch (e) {
                            console.error("Erro ao carregar background:", e);
                        }
                    }

                    switchPage(0);
                }

                render();
                rebuildLayersUI();
            }
        } catch (error) {
            console.error("Erro ao carregar modelo:", error);
            Swal.fire({
                icon: "error",
                title: "Erro",
                text: "Não foi possível carregar o modelo.",
                confirmButtonColor: "green",
                confirmButtonText: "Ok",
                theme: `${temaAlerta}`,
            });
        }
    }

    async function processarElemento(el, pageIndex) {
        const novoElemento = { ...el };

        if (!novoElemento.id) {
            novoElemento.id = uid();
        }

        if (novoElemento.type === "text" && !novoElemento.verticalAlign) {
            novoElemento.verticalAlign = "middle";
        }

        if (novoElemento.type === "image" && novoElemento.src) {
            try {
                const img = await loadImage(novoElemento.src);
                novoElemento.imgObj = img;
            } catch (e) {
                console.error("Erro ao carregar imagem:", e);
            }
        }

        pages[pageIndex].elements.push(novoElemento);
    }

    window.carregarModeloExistente = carregarModeloExistente;

    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const modeloId = urlParams.get("id");

        if (modeloId) {
            saveModelBtn.innerHTML = '<i class="bi bi-save"></i> Atualizar';
            carregarModeloExistente(modeloId);
        }
    });

    function readFileAsDataURL(file) {
        return new Promise((res) => {
            const fr = new FileReader();
            fr.onload = () => res(fr.result);
            fr.readAsDataURL(file);
        });
    }

    function loadImage(src) {
        return new Promise((res, rej) => {
            const i = new Image();
            i.onload = () => res(i);
            i.onerror = rej;
            i.src = src;
            i.crossOrigin = "anonymous";
        });
    }

    document.querySelectorAll("[data-tag]").forEach((b) =>
        b.addEventListener("click", () => {
            const tag = b.dataset.tag;
            if (!selectedId) return;
            const el = elements.find((x) => x.id === selectedId);
            if (!el || el.type !== "text") return;
            
            const range = quill.getSelection();
            if (range) {
                quill.insertText(range.index, ` ${tag}`);
            } else {
                quill.insertText(quill.getLength(), ` ${tag}`);
            }
        })
    );

    orientationSelect.addEventListener("change", (e) => {
        setCanvasForOrientation(e.target.value);
    });

    document.getElementById("addTabelaConteudoBtn").addEventListener("click", function () {
        const id = uid();
        const el = {
            id,
            type: "text",
            x: canvas.width * 0.1,
            y: canvas.height * 0.6,
            w: canvas.width * 0.8,
            h: 200,
            text: "<p><strong>CONTEÚDO PROGRAMÁTICO:</strong></p><p>{conteudo programático}</p>",
            font: "helvetica",
            fontSize: 14,
            fontWeight: "normal",
            color: "#222222",
            align: "left",
            opacity: 1,
            rotate: 0,
        };
        elements.push(el);
        selectElement(id);
        render();
    });

    saveModelBtn.addEventListener("click", async () => {
        

        const nomeModelo = modelName.value.trim();
        if (!nomeModelo)
            return Swal.fire({
                icon: "error",
                title: "Erro!",
                text: "Por favor, insira um nome para o modelo.",
                confirmButtonColor: "green",
                confirmButtonText: "Ok",
                theme: `${temaAlerta}`,
            });

        const urlParams = new URLSearchParams(window.location.search);
        const modeloId = urlParams.get("id");

        const paginasSerializadas = pages.map((page) => {
            const elementosSerializados = page.elements.map((el) => {
                const elemento = {
                    id: el.id,
                    type: el.type,
                    x: el.x,
                    y: el.y,
                    w: el.w,
                    h: el.h,
                    opacity: el.opacity,
                    rotate: el.rotate,
                };

                if (el.type === "text") {
                    elemento.text = el.text;
                    elemento.font = el.font;
                    elemento.fontSize = el.fontSize;
                    elemento.fontWeight = el.fontWeight;
                    elemento.color = el.color;
                    elemento.align = el.align;
                    elemento.verticalAlign = el.verticalAlign || "middle";
                } else if (el.type === "image") {
                    elemento.src = el.src;
                } else if (el.type === "qrcode") {
                    elemento.text = el.text;
                }

                return elemento;
            });

            return {
                elementos: elementosSerializados,
                background: page.backgroundData,
            };
        });

        const highResCanvas = renderHighResolution();
        const highResDataUrl = highResCanvas.toDataURL("image/png", 1.0);

        const modelData = {
            nome: nomeModelo,
            orientacao: orientation,
            elementos: JSON.stringify(paginasSerializadas[0].elementos),
            paginas: JSON.stringify(paginasSerializadas),
        };

        if (modeloId) {
            modelData.id = modeloId;
        }

        try {
            const url = modeloId
                ? "/admin/certificados/atualizar-modelo"
                : "/admin/certificados/salvar-modelo";


            switch (url) {
                case "/admin/certificados/salvar-modelo":
                    saveModelBtn.disabled = true;
                    saveModelBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando';
                    break;
                case "/admin/certificados/atualizar-modelo":
                    saveModelBtn.disabled = true;
                    saveModelBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Atualizando';
                    break;
            }

            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(modelData),
            });

            const result = await response.json();

            if (result.status) {
                Swal.fire({
                    icon: "success",
                    title: "Sucesso",
                    html: modeloId
                        ? "Modelo atualizado com sucesso!"
                        : "Modelo salvo com sucesso! Você pode visualizar o modelo na página de geração de certificados <a href='/admin/certificados/listar-modelos'>clicando aqui</a>.",
                    confirmButtonColor: "green",
                    confirmButtonText: "Ok",
                    theme: `${temaAlerta}`,
                }).then(() => {
                    if (!modeloId) {
                        window.location.href = "/admin/certificados/gerar-modelo";
                    }
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Erro!",
                    text: "Erro ao salvar modelo: " + result.mensagem,
                    confirmButtonColor: "green",
                    confirmButtonText: "Ok",
                    theme: `${temaAlerta}`,
                });
            }
        } catch (error) {
            Swal.fire({
                icon: "error",
                title: "Erro!",
                text: "Erro ao conectar com o servidor: " + error,
                confirmButtonColor: "green",
                confirmButtonText: "Ok",
                theme: `${temaAlerta}`,
            });
        } finally {
            const urlParams = new URLSearchParams(window.location.search);
            const modeloId = urlParams.get("id");


            if (modeloId) {
                saveModelBtn.disabled = false;
                saveModelBtn.innerHTML = '<i class="bi bi-save"></i> Atualizar';
                console.log('aquiiii atualizar');
                carregarModeloExistente(modeloId);
                return;
            }

            saveModelBtn.disabled = false;
            saveModelBtn.innerHTML = '<i class="bi bi-save"></i> Salvar';
        }
    });

    setInterval(() => render(), 200);
    rebuildLayersUI();

    window._certEditor = {
        elements,
        render,
        selectElement,
        canvas,
        pages,
        currentPageIndex,
        quill
    };
})();