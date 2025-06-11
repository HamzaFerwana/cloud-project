@extends('site.master')
@section('title', 'Search Your Documents | ' . env('APP_NAME'))

@section('styles')

    <style>
        canvas {
            display: block;
            margin: 10px auto;
            border: 1px solid #aaa;
        }

        mark {
            background-color: yellow;
            font-weight: bold;
        }

        #output {
            border: 1px solid #ccc;
            padding: 10px;
            max-height: 400px;
            overflow-y: auto;
            font-family: Arial, sans-serif;
        }
    </style>

@endsection


@section('content')

    <h2>Select a Stored PDF or Word File</h2>
    <select id="stored-files" class="form-control" style="background-color: rgb(231, 199, 199)">
        <option value="">-- Select a file --</option>
        @foreach ($files as $file)
            <option value="{{ asset($file->file) }}">{{ basename($file->file) }}</option>
        @endforeach
    </select>

    <br><br>

    <label>Search Keyword:</label>
    <input type="text" id="keyword" class="form-control mb-3" placeholder="search..." />
    <button id="search-btn" class="btn btn-primary mb-3">Search and Highlight</button>
    <button id="download-btn" disabled class="btn btn-success mb-3">Download Highlighted File (.docx)</button>

    <div id="search-time" style="display: none; margin-bottom: 15px;"></div>

    <h3>Result:</h3>
    <div id="output" style="display:none;"></div>
    <div id="pdf-container"></div>

@endsection






@section('scripts')
    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.8.162/pdf.min.js"></script>
    <!-- Mammoth for DOCX -->
    <script src="https://unpkg.com/mammoth/mammoth.browser.min.js"></script>
    <!-- docx.js -->
    <script src="https://cdn.jsdelivr.net/npm/docx@7.6.0/build/index.min.js"></script>
    <!-- FileSaver -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <!-- PDFLib -->
    <script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>

    <script>
        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        const docx = window.docx;

        const fileSelect = document.getElementById('stored-files');
        const keywordInput = document.getElementById('keyword');
        const searchBtn = document.getElementById('search-btn');
        const downloadBtn = document.getElementById('download-btn');
        const outputDiv = document.getElementById('output');
        const pdfContainer = document.getElementById('pdf-container');

        let currentFile = null;
        let currentFileType = null;
        let pdfTextContents = [];

        fileSelect.addEventListener('change', async (e) => {
            const url = e.target.value;
            if (!url) return;

            const response = await fetch(url);
            const originalBuffer = await response.arrayBuffer();
            const fileName = url.split('/').pop();
            const fileType = fileName.endsWith('.pdf') ? 'pdf' : 'docx';

            currentFile = {
                name: fileName,
                getBuffer: () => originalBuffer.slice(0)
            };
            currentFileType = fileType;

            pdfTextContents = [];
            pdfContainer.innerHTML = '';
            outputDiv.innerHTML = '';
            outputDiv.style.display = 'none';
            downloadBtn.disabled = true;
        });

        searchBtn.addEventListener('click', () => {
            const keyword = keywordInput.value.trim();
            if (!currentFile?.getBuffer || !keyword) {
                alert('Please select a file and enter a keyword.');
                return;
            }

            if (currentFileType === 'pdf') {
                handlePDF(currentFile, keyword);
            } else if (currentFileType === 'docx') {
                handleDocx(currentFile, keyword);
            }
        });

        async function handlePDF(file, keyword) {
            const start = performance.now();

            const arrayBuffer = file.getBuffer();
            const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);
            const pdf = await pdfjsLib.getDocument({
                data: arrayBuffer
            }).promise;

            let matchFound = false;
            const keywordLower = keyword.toLowerCase();

            for (let i = 0; i < pdf.numPages; i++) {
                const page = await pdf.getPage(i + 1);
                const textContent = await page.getTextContent();
                pdfTextContents[i] = textContent.items.map(item => item.str).join(' ');

                const pageLib = pdfDoc.getPages()[i];

                for (const item of textContent.items) {
                    const text = item.str;
                    const lowerText = text.toLowerCase();
                    const regex = new RegExp(escapeRegExp(keywordLower), 'gi');

                    let match;
                    while ((match = regex.exec(lowerText)) !== null) {
                        matchFound = true;

                        const startIndex = match.index;
                        const matchedText = match[0];
                        const avgCharWidth = item.width / text.length;
                        const matchWidth = avgCharWidth * matchedText.length;
                        const offsetX = avgCharWidth * startIndex;

                        const {
                            transform
                        } = item;
                        const x = transform[4] + offsetX;
                        const y = transform[5];
                        const height = Math.abs(transform[0]) * 0.9;

                        pageLib.drawRectangle({
                            x,
                            y: y - height * 0.3,
                            width: matchWidth,
                            height,
                            color: PDFLib.rgb(1, 1, 0),
                            opacity: 0.5,
                        });
                    }
                }
            }

            const end = performance.now();
            const duration = ((end - start) / 1000).toFixed(3);

            const timeDiv = document.getElementById('search-time');
            timeDiv.style.display = 'block';
            timeDiv.innerHTML = `<h3>Search Time: ${duration} seconds</h3>`;

            outputDiv.style.display = 'block';
            outputDiv.innerHTML = '';

            if (!matchFound) {
                const msg = document.createElement('p');
                msg.textContent = `Keyword "${keyword}" not found in PDF: ${file.name}`;
                msg.style.color = 'red';
                outputDiv.appendChild(msg);
                return;
            }

            const modifiedPdf = await pdfDoc.save();
            const blob = new Blob([modifiedPdf], {
                type: 'application/pdf'
            });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `highlighted-${file.name}`;
            link.textContent = `Download highlighted PDF: ${file.name}`;
            link.style.display = 'block';
            outputDiv.appendChild(link);
        }

        async function handleDocx(file, keyword) {
            const start = performance.now();

            const arrayBuffer = file.getBuffer();
            mammoth.convertToHtml({
                    arrayBuffer: arrayBuffer
                })
                .then(result => {
                    const html = result.value;
                    const regex = new RegExp(`(${escapeRegExp(keyword)})`, 'gi');
                    const highlighted = html.replace(regex, '<mark>$1</mark>');

                    const end = performance.now();
                    const duration = ((end - start) / 1000).toFixed(3);

                    const timeDiv = document.getElementById('search-time');
                    timeDiv.style.display = 'block';
                    timeDiv.innerHTML = `<h3>Search Time: ${duration} seconds</h3>`;

                    outputDiv.innerHTML = highlighted;
                    outputDiv.style.display = 'block';
                    downloadBtn.disabled = false;
                })
                .catch(error => alert('Error reading Word file: ' + error));
        }


        downloadBtn.addEventListener('click', async () => {
            const keyword = keywordInput.value.trim();
            if (!currentFile?.getBuffer || !keyword) {
                alert('Please search first.');
                return;
            }

            if (currentFileType === 'pdf') {
                const pdfDoc = await PDFLib.PDFDocument.load(currentFile.getBuffer());
                const helveticaFont = await pdfDoc.embedFont(PDFLib.StandardFonts.HelveticaBold);
                const pages = pdfDoc.getPages();

                pages.forEach((page, i) => {
                    if (pdfTextContents[i]?.toLowerCase().includes(keyword.toLowerCase())) {
                        const {
                            width,
                            height
                        } = page.getSize();
                        page.drawText(`Keyword "${keyword}" found`, {
                            x: 50,
                            y: height - 50,
                            size: 14,
                            font: helveticaFont,
                            color: PDFLib.rgb(1, 0.8, 0),
                        });
                    }
                });

                const modifiedBytes = await pdfDoc.save();
                const blob = new Blob([modifiedBytes], {
                    type: 'application/pdf'
                });
                saveAs(blob, 'highlighted.pdf');
            } else if (currentFileType === 'docx') {
                const htmlContent = outputDiv.innerHTML;
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlContent, 'text/html');
                const body = doc.body;

                const paragraphs = [];

                function processNode(node) {
                    if (node.nodeType === 3) {

                        return new docx.TextRun({
                            text: node.textContent || ""
                        });
                    }

                    if (node.nodeType === 1) {
                        const tag = node.tagName.toUpperCase();


                        if (tag === 'MARK') {
                            const innerText = node.textContent || "";
                            return new docx.TextRun({
                                text: innerText,
                                highlight: 'yellow'
                            });
                        }


                        const childRuns = [];
                        node.childNodes.forEach(child => {
                            const run = processNode(child);
                            if (Array.isArray(run)) {
                                childRuns.push(...run);
                            } else if (run) {
                                childRuns.push(run);
                            }
                        });

                        return childRuns;
                    }

                    return null;
                }


                function processElement(element) {
                    const runs = [];
                    element.childNodes.forEach(child => {
                        const run = processNode(child);
                        if (Array.isArray(run)) {
                            runs.push(...run);
                        } else if (run) {
                            runs.push(run);
                        }
                    });

                    if (runs.length > 0) {
                        return new docx.Paragraph({
                            children: runs
                        });
                    }

                    return null;
                }



                body.childNodes.forEach(node => {
                    const paragraph = processElement(node);
                    if (paragraph) paragraphs.push(paragraph);
                });

                const docxFile = new docx.Document({
                    sections: [{
                        children: paragraphs
                    }]
                });

                const blob = await docx.Packer.toBlob(docxFile);
                saveAs(blob, 'highlighted.docx');
            }

        });

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
    </script>





@endsection
