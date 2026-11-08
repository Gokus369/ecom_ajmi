<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ColourModel;
use App\Models\OptionListModel;
use App\Models\SizeModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * The master lists of colours and sizes. Both share one page, so their field names
 * start with the list, which keeps each form's errors and typed values to itself.
 */
class Options extends BaseController
{
    public function index(): string
    {
        return view('admin/options/index', [
            'title'   => 'Colours and sizes',
            'colours' => model(ColourModel::class)->withPieceCounts(),
            'sizes'   => model(SizeModel::class)->withPieceCounts(),
        ]);
    }

    public function create(string $list): RedirectResponse
    {
        $options = $this->options($list);
        $data    = $this->input($list);

        if (! $this->validateData($data, $this->rules($list))) {
            $errors = [];

            foreach ($this->validator->getErrors() as $field => $message) {
                $errors["{$list}_{$field}"] = $message;
            }

            return redirect()->route('admin.options')->withInput()->with('errors', $errors);
        }

        if ($this->posted("{$list}_sort_order") === '') {
            $data['sort_order'] = $options->countAllResults() + 1;
        }

        $options->insert($data);

        return redirect()->route('admin.options')->with('message', "{$data['name']} added.");
    }

    public function update(string $list, int $id): RedirectResponse
    {
        $options = $this->options($list);
        $this->findOrFail($options, $id);

        $data = $this->input($list);

        if (! $this->validateData($data, $this->rules($list, $id))) {
            return redirect()->route('admin.options')->with('error', implode(' ', $this->validator->getErrors()));
        }

        $options->update($id, $data);

        return redirect()->route('admin.options')->with('message', "{$data['name']} saved.");
    }

    public function delete(string $list, int $id): RedirectResponse
    {
        $options = $this->options($list);
        $option  = $this->findOrFail($options, $id);
        $pieces  = $options->piecesUsing($id);

        if ($pieces > 0) {
            return redirect()->route('admin.options')
                ->with('error', "{$option->name} is used on {$pieces} " . ($pieces === 1 ? 'piece' : 'pieces') . '. Untick it there first.');
        }

        $options->delete($id);

        return redirect()->route('admin.options')->with('message', "{$option->name} deleted.");
    }

    private function options(string $list): OptionListModel
    {
        return $list === 'colours' ? model(ColourModel::class) : model(SizeModel::class);
    }

    private function findOrFail(OptionListModel $options, int $id): object
    {
        $option = $options->find($id);

        if ($option === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $option;
    }

    /**
     * @return array<string, int|string>
     */
    private function input(string $list): array
    {
        $data = [
            'name'       => $this->posted("{$list}_name"),
            'sort_order' => (int) $this->posted("{$list}_sort_order"),
        ];

        if ($list === 'colours') {
            $data['hex'] = strtolower($this->posted('colours_hex'));
        }

        return $data;
    }

    private function posted(string $field): string
    {
        $value = $this->request->getPost($field);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(string $list, int $id = 0): array
    {
        $rules = [
            'name' => [
                'rules'  => "required|max_length[40]|is_unique[{$list}.name,id,{$id}]",
                'errors' => [
                    'required'   => 'Please enter a name.',
                    'max_length' => 'Names can be up to 40 characters.',
                    'is_unique'  => 'That name is already on the list.',
                ],
            ],
            'sort_order' => 'permit_empty|integer',
        ];

        if ($list === 'colours') {
            $rules['hex'] = [
                'rules'  => ['required', 'regex_match[/^#[0-9a-f]{6}$/]'],
                'errors' => ['required' => 'Please choose a swatch colour.', 'regex_match' => 'Please choose a swatch colour.'],
            ];
        }

        return $rules;
    }
}
