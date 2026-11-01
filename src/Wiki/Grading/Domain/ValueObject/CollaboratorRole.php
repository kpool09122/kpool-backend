<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\ValueObject;

enum CollaboratorRole: string
{
    case COLLABORATOR = 'Collaborator';
    case SENIOR_COLLABORATOR = 'SeniorCollaborator';
}
