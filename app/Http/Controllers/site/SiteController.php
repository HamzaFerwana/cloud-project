<?php

namespace App\Http\Controllers\site;

use App\Models\UserFile;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Smalot\PdfParser\Parser as PdfParser;
use Illuminate\Support\Facades\File as FileFacade;



class SiteController extends Controller
{
    public function index()
    {
        $filesCount = UserFile::where('user_id', Auth::id())->count();

        $start = microtime(true);

        $files = UserFile::where('user_id', Auth::id())
            ->orderBy('title', 'ASC')
            ->paginate(5);

        $end = microtime(true);

        $sortingTime = round($end - $start, 4);

        foreach ($files as $file) {
            $filePath = public_path($file->file);

            if (file_exists($filePath)) {
                $file->size = $this->formatFileSize(filesize($filePath));
            } else {
                $file->size = 'File not found';
            }
        }

        return view('site.index', compact('files', 'filesCount', 'sortingTime'));
    }


    protected function formatFileSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    public function upload_files(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:10240'
        ]);

        $file = $request->file('file');

        $ext = strtolower($file->getClientOriginalExtension());

        $subfolder = match ($ext) {
            'pdf' => 'pdf',
            'docx' => 'word',
            default => 'others',
        };


        $originalName = $file->getClientOriginalName();
        $path = $file->storeAs("uploads/files/{$subfolder}", $originalName, 'custom');

        $title = $this->extractTitle($path);

        UserFile::create([
            'user_id' => Auth::id(),
            'file' => $path,
            'file_type' => $subfolder,
            'title' => $title
        ]);

        return redirect()->route('cloud-project.index')->with(['msg' => 'Your file was stored successfully.', 'type' => 'success']);
    }




    public function delete_file($id)
    {

        $file = UserFile::findOrFail($id);


        $filePath = public_path($file->file);


        if (FileFacade::exists($filePath)) {
            FileFacade::delete($filePath);
        }


        $file->delete();


        return redirect()->back()->with('msg', 'File deleted successfully.')->with('type', 'danger');
    }

    public function view_file($id)
    {
        $file = UserFile::findOrFail($id);
        return view('site.viewfile', compact('file'));
    }


    public function extractTitle($filePath)
    {
        if (!file_exists($filePath)) {
            return 'Error: File does not exist';
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            $metadataTitle = null;

            if ($extension === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                $metadata = $pdf->getDetails();
                if (isset($metadata['Title'])) {
                    $metadataTitle = is_array($metadata['Title'])
                        ? trim($metadata['Title'][0])
                        : trim($metadata['Title']);
                }
            } elseif ($extension === 'docx') {
                $phpWord = IOFactory::load($filePath);
                $docInfo = $phpWord->getDocInfo();
                $metadataTitle = trim($docInfo->getTitle());
            }

            if (!empty($metadataTitle)) {
                return mb_convert_encoding($metadataTitle, 'UTF-8', 'auto');
            }

            return 'Untitled';
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function search_doucments()
    {
        $files = Auth::user()->files;
        return view('site.search-documents', compact('files'));
    }

    protected $classificationTree = [
        'Education' => [
            'Course' => ['syllabus', 'course outline', 'lesson plan'],
            'Exam' => ['exam schedule', 'test results', 'grading policy'],
        ],
        'Business' => [
            'Finance' => ['invoice', 'budget', 'financial report'],
            'HR' => ['resume', 'job description', 'employee record'],
        ],
        'Medical' => [
            'Appointments' => ['appointment confirmation', 'appointment schedule', 'visit summary'],
            'Prescriptions' => ['prescription', 'medication', 'refill request'],
        ],
    ];



    protected function classifyTitle($title)
    {
        $titleLower = strtolower($title);

        foreach ($this->classificationTree as $level1 => $level2Array) {
            foreach ($level2Array as $level2 => $level3Keywords) {
                foreach ($level3Keywords as $level3) {
                    if (str_contains($titleLower, strtolower($level3))) {
                        return [
                            'level1' => $level1,
                            'level2' => $level2,
                            'level3' => $level3,
                        ];
                    }
                }
            }
        }

        foreach ($this->classificationTree as $level1 => $level2Array) {
            foreach ($level2Array as $level2 => $level3Keywords) {
                if (str_contains($titleLower, strtolower($level2))) {
                    return [
                        'level1' => $level1,
                        'level2' => $level2,
                        'level3' => null,
                    ];
                }
            }
        }

        foreach ($this->classificationTree as $level1 => $level2Array) {
            if (str_contains($titleLower, strtolower($level1))) {
                return [
                    'level1' => $level1,
                    'level2' => null,
                    'level3' => null,
                ];
            }
        }

        return [
            'level1' => 'Uncategorized',
            'level2' => null,
            'level3' => null,
        ];
    }

    public function classify_doucments()
    {

        $filesCount = UserFile::where('user_id', Auth::id())->count();
        $files = UserFile::where('user_id', Auth::id())->orderBy('title', 'ASC')->paginate(5);

        $start = microtime(true);
        foreach ($files as $file) {
            $file->classification = $this->classifyTitle($file->title);
        }
        $end = microtime(true);
        $classificationTime = round($end - $start, 4);

        return view('site.classify-documents', compact('files', 'filesCount', 'classificationTime'));
    }
}
