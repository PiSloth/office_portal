<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $headers = [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="users_import_template.csv"',
                    ];

                    $callback = function () {
                        $file = fopen('php://output', 'w');
                        fputcsv($file, ['email', 'name', 'branch', 'role']);
                        fputcsv($file, ['checker1@example.com', 'Ko Hlaing Myo Aung', 'Branch One', 'checker']);
                        fputcsv($file, ['manager1@example.com', 'Ame Zin', 'Branch One', 'manager']);
                        fclose($file);
                    };

                    return response()->stream($callback, 200, $headers);
                }),

            Actions\Action::make('import_users')
                ->label('Import Users')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('CSV File')
                        ->disk('public')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain']),
                ])
                ->action(function (array $data) {
                    $disk = 'public';
                    $content = Storage::disk($disk)->get($data['file']);
                    if (!$content) {
                        Notification::make()
                            ->title('Error')
                            ->body('Failed to read uploaded file.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $stream = fopen('php://temp', 'r+');
                    fwrite($stream, $content);
                    rewind($stream);

                    $headers = fgetcsv($stream);
                    if (!$headers) {
                        Notification::make()
                            ->title('Error')
                            ->body('Empty CSV file.')
                            ->danger()
                            ->send();
                        fclose($stream);
                        return;
                    }

                    // Auto-detection rules
                    $emailIndex = -1;
                    $nameIndex = -1;
                    $branchIndex = -1;
                    $roleIndex = -1;

                    $sampleRows = [];
                    while (($row = fgetcsv($stream)) !== false && count($sampleRows) < 5) {
                        $sampleRows[] = $row;
                    }
                    rewind($stream);
                    fgetcsv($stream); // Skip headers

                    $colCount = count($headers);
                    for ($i = 0; $i < $colCount; $i++) {
                        $isEmail = false;
                        $isBranch = false;
                        $isRole = false;
                        $nonEmptyCount = 0;

                        foreach ($sampleRows as $row) {
                            if (!isset($row[$i])) continue;
                            $val = trim($row[$i]);
                            if ($val === '') continue;
                            $nonEmptyCount++;

                            if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                                $isEmail = true;
                            }

                            if (preg_match('/^[bB]\d+$/i', $val) || preg_match('/^branch\s*\d+/i', $val)) {
                                $isBranch = true;
                            }

                            $cleanVal = strtolower(trim(str_replace(' ', '-', $val)));
                            if (in_array($cleanVal, ['cashier', 'sale-person', 'manager', 'checker', 'super-admin', 'admin', 'checker-staff', 'checker-supervisor'])) {
                                $isRole = true;
                            }
                        }

                        $headerClean = strtolower(trim($headers[$i]));
                        if ($isEmail) {
                            $emailIndex = $i;
                        } elseif ($isBranch || str_contains($headerClean, 'branch')) {
                            $branchIndex = $i;
                        } elseif ($isRole || str_contains($headerClean, 'role')) {
                            $roleIndex = $i;
                        } elseif ($nonEmptyCount > 0 && $nameIndex === -1 && str_contains($headerClean, 'name')) {
                            $nameIndex = $i;
                        }
                    }

                    // Fallbacks
                    if ($emailIndex === -1) {
                        foreach ($headers as $i => $h) {
                            if (str_contains(strtolower($h), 'email')) { $emailIndex = $i; break; }
                        }
                    }
                    if ($nameIndex === -1) {
                        foreach ($headers as $i => $h) {
                            if (str_contains(strtolower($h), 'name') && !str_contains(strtolower($h), 'branch')) { $nameIndex = $i; break; }
                        }
                    }
                    if ($branchIndex === -1) {
                        foreach ($headers as $i => $h) {
                            if (str_contains(strtolower($h), 'branch')) { $branchIndex = $i; break; }
                        }
                    }
                    if ($roleIndex === -1) {
                        foreach ($headers as $i => $h) {
                            if (str_contains(strtolower($h), 'role')) { $roleIndex = $i; break; }
                        }
                    }

                    if ($emailIndex === -1 || $nameIndex === -1) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Could not identify Name or Email columns. Please ensure they are present.')
                            ->danger()
                            ->send();
                        fclose($stream);
                        return;
                    }

                    $successCount = 0;
                    $updateCount = 0;
                    $failCount = 0;

                    while (($row = fgetcsv($stream)) !== false) {
                        if (empty($row) || !isset($row[$emailIndex])) continue;
                        $email = trim($row[$emailIndex]);
                        if ($email === '') continue;

                        $name = trim($row[$nameIndex] ?? '');
                        $branchVal = trim($row[$branchIndex] ?? '');
                        $roleVal = trim($row[$roleIndex] ?? '');

                        try {
                            // Resolve Branch
                            $branchId = null;
                            if ($branchVal !== '') {
                                $branch = Branch::where('name', $branchVal)->first();
                                if (!$branch) {
                                    $failCount++;
                                    continue; // Skip import if branch is not found
                                }
                                $branchId = $branch->id;
                            } else {
                                $failCount++;
                                continue; // Skip if branch name is empty
                            }

                            // Resolve Role
                            $role = null;
                            if ($roleVal !== '') {
                                $roleName = strtolower(trim(str_replace(' ', '-', $roleVal)));
                                $role = Role::where('name', $roleVal)
                                    ->orWhere('name', $roleName)
                                    ->first();
                                if (!$role) {
                                    $failCount++;
                                    continue; // Skip import if role is not found
                                }
                            } else {
                                $failCount++;
                                continue; // Skip if role is empty
                            }

                            // Create/Update User
                            $user = User::where('email', $email)->first();
                            if ($user) {
                                $userData = ['name' => $name];
                                if ($branchId) {
                                    $userData['branch_id'] = $branchId;
                                }
                                $user->update($userData);
                                $updateCount++;
                            } else {
                                $user = User::create([
                                    'name' => $name,
                                    'email' => $email,
                                    'password' => Hash::make('Password123!'),
                                    'status' => 'ACTIVE',
                                    'branch_id' => $branchId,
                                ]);
                                $successCount++;
                            }

                            // Assign Role
                            if ($role) {
                                $user->syncRoles([$role->name]);
                            }
                        } catch (\Exception $e) {
                            $failCount++;
                        }
                    }

                    fclose($stream);

                    Notification::make()
                        ->title('Import Complete')
                        ->body("Successfully imported {$successCount} new users, updated {$updateCount} users, and failed on {$failCount} users.")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
