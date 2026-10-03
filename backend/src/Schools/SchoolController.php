<?php

declare(strict_types=1);

namespace App\Schools;

use App\Auth\CurrentUser;
use App\Shared\ImageUpload;
use App\Shared\PhoneNumber;
use App\Users\Service\UserException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class SchoolController
{
    public function __construct(private ConnectionInterface $db, private ResponseFactoryInterface $responses)
    {
    }

    public function view(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json(200, $this->find($this->schoolId($request)));
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);
        $name = is_array($body) && is_string($body['name'] ?? null) ? trim($body['name']) : '';
        if ($name === '' || mb_strlen($name) > 255) {
            return \App\Shared\JsonResponse::error($this->responses, 400, 'Invalid school name.');
        }
        $contact = [];
        if (!is_array($body)) {
            return \App\Shared\JsonResponse::error($this->responses, 400, 'Invalid school data.');
        }
        foreach (['address', 'phone', 'email'] as $field) {
            $value = $body[$field] ?? null;
            if ($value !== null && !is_string($value)) {
                return \App\Shared\JsonResponse::error($this->responses, 400, "Invalid school {$field}.");
            }
            $value = is_string($value) ? trim($value) : '';
            $contact[$field] = $value === '' ? null : $value;
        }
        if (($contact['address'] !== null && mb_strlen($contact['address']) > 255)
            || ($contact['phone'] !== null && PhoneNumber::normalize($contact['phone']) === null)
            || ($contact['email'] !== null && (!filter_var($contact['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($contact['email']) > 255))
        ) {
            return \App\Shared\JsonResponse::error($this->responses, 400, 'Invalid school contact data.');
        }
        if ($contact['phone'] !== null) {
            $contact['phone'] = PhoneNumber::normalize($contact['phone']);
        }
        $schoolId = $this->schoolId($request);
        $params = [':name' => $name, ':id' => $schoolId];
        foreach ($contact as $field => $value) {
            $params[':' . $field] = $value;
        }
        $this->db->createCommand(
            'UPDATE schools SET name = :name, address = :address, phone = :phone, email = :email WHERE id = :id',
            $params,
        )->execute();
        return $this->json(200, $this->find($schoolId));
    }

    public function uploadLogo(ServerRequestInterface $request): ResponseInterface
    {
        $temporaryPath = null;
        try {
            $upload = $request->getUploadedFiles()['logo'] ?? null;
            if (!$upload instanceof UploadedFileInterface || $upload->getError() !== UPLOAD_ERR_OK || $upload->getSize() === null) {
                throw new UserException('A logo upload is required.', 400);
            }
            $temporaryPath = tempnam(sys_get_temp_dir(), 'school-logo-');
            if ($temporaryPath === false) {
                throw new UserException('Could not read logo upload.', 400);
            }
            $upload->moveTo($temporaryPath);
            $schoolId = $this->schoolId($request);
            $path = ImageUpload::store($temporaryPath, (int) $upload->getSize(), dirname(__DIR__, 2) . '/public/uploads/school', '/uploads/school/', 'logo-' . $schoolId . '-');
            $oldPath = $this->logoPath($schoolId);
            $this->db->createCommand('UPDATE schools SET logo_path = :path WHERE id = :id', [':path' => $path, ':id' => $schoolId])->execute();
            if ($oldPath !== null) {
                ImageUpload::delete($oldPath, '/uploads/school/', dirname(__DIR__, 2) . '/public');
            }
            return $this->json(200, $this->find($schoolId));
        } catch (UserException $exception) {
            return \App\Shared\JsonResponse::error($this->responses, $exception->status, $exception->getMessage());
        } catch (\InvalidArgumentException $exception) {
            $status = str_contains($exception->getMessage(), '2 MB') ? 413 : 400;
            return \App\Shared\JsonResponse::error($this->responses, $status, $exception->getMessage());
        } catch (\Throwable) {
            return \App\Shared\JsonResponse::error($this->responses, 500, 'Unexpected server error.');
        } finally {
            if (is_string($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    public function deleteLogo(ServerRequestInterface $request): ResponseInterface
    {
        $schoolId = $this->schoolId($request);
        $path = $this->logoPath($schoolId);
        $this->db->createCommand('UPDATE schools SET logo_path = NULL WHERE id = :id', [':id' => $schoolId])->execute();
        if ($path !== null) {
            ImageUpload::delete($path, '/uploads/school/', dirname(__DIR__, 2) . '/public');
        }
        return $this->json(200, $this->find($schoolId));
    }

    private function find(int $id): array
    {
        $school = $this->db->createCommand('SELECT id, name, address, phone, email, logo_path FROM schools WHERE id = :id', [':id' => $id])->queryOne();
        $school['logo_url'] = $school['logo_path'];
        unset($school['logo_path']);
        return $school;
    }

    private function logoPath(int $id): ?string
    {
        $path = $this->db->createCommand('SELECT logo_path FROM schools WHERE id = :id', [':id' => $id])->queryScalar();
        return is_string($path) ? $path : null;
    }

    private function schoolId(ServerRequestInterface $request): int
    {
        return $request->getAttribute(CurrentUser::class)->schoolId;
    }

    private function json(int $status, mixed $data): ResponseInterface
    {
        return \App\Shared\JsonResponse::send($this->responses, $status, $data);
    }
}
