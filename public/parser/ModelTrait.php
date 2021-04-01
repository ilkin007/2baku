<?php

/**
 * Trait Model
 */
trait Model
{
    /**
     * @param string $city
     *
     * @return int
     */
    public function insertOrGetCity(string $city): int
    {
        $id = $this->getIdByParameter('cities', ['title' => $city]);

        if ($id > 0) {
            return $id;
        }

        $sql = "INSERT INTO cities (`title`) VALUES ('{$city}')";
        $q = $this->query($sql);

        if ($q) {
            return $this->getIdByParameter('cities', ['title' => $city]);
        }

        return 0;
    }

    /**
     * @param string $federalState
     *
     * @return int
     */
    public function insertOrGetFederalState(string $federalState): int
    {
        $id = $this->getIdByParameter('federal_states', ['title' => $federalState]);

        if ($id > 0) {
            return $id;
        }

        $sql = "INSERT INTO federal_states (`title`) VALUES ('{$federalState}')";
        $q = $this->query($sql);

        if ($q) {
            return $this->getIdByParameter('federal_states', ['title' => $federalState]);
        }

        return 0;
    }

    /**
     * @param string $universityType
     *
     * @return int
     */
    public function insertOrGetUniversityType(string $universityType): int
    {
        $id = $this->getIdByParameter('university_types', ['title' => $universityType]);

        if ($id > 0) {
            return $id;
        }

        $sql = "INSERT INTO university_types (`title`) VALUES ('{$universityType}')";
        $q = $this->query($sql);

        if ($q) {
            return $this->getIdByParameter('university_types', ['title' => $universityType]);
        }

        return 0;
    }

    /**
     * @param string $degree
     *
     * @return int
     */
    public function insertOrGetDegree(string $degree): int
    {
        $id = $this->getIdByParameter('degrees', ['title' => $degree]);

        if ($id > 0) {
            return $id;
        }

        $sql = "INSERT INTO degrees (`title`) VALUES ('{$degree}')";
        $q = $this->query($sql);

        if ($q) {
            return $this->getIdByParameter('degrees', ['title' => $degree]);
        }

        return 0;
    }

    /**
     * @param string $semester
     *
     * @return int
     */
    public function insertOrGetSemester(string $semester): int
    {
        $id = $this->getIdByParameter('semesters', ['title' => $semester]);

        if ($id > 0) {
            return $id;
        }

        $sql = "INSERT INTO semesters (`title`) VALUES ('{$semester}')";
        $q = $this->query($sql);

        if ($q) {
            return $this->getIdByParameter('semesters', ['title' => $semester]);
        }

        return 0;
    }

    /**
     * @param string $table
     * @param array $parameters
     * @param bool $showSql
     *
     * @return int
     */
    public function getIdByParameter(string $table, array $parameters, bool $showSql = false): int
    {
        $parametersString = '';
        foreach ($parameters as $parameter => $value) {
            $parametersString .= "`{$parameter}`='{$value}' AND ";
        }

        //Removing last AND with leading and trailing space
        $parametersString = substr($parametersString, 0, -5);


        $sql = "SELECT * FROM `$table` WHERE {$parametersString}";

        $query = $this->query($sql);

        if ($this->numRows($query) > 0) {
            $q = $this->fetch($query);

            return (int)$q['id'];
        } else {
            if ($showSql) {
                echo $sql;
            }
        }

        return 0;
    }

    /**
     * @param int $id
     * @param array $array
     *
     * @return bool
     */
    private function updateUniversity(int $id, array $array): bool
    {
        $cityId = $this->insertOrGetCity($array['city']);
        $federalStateId = $this->insertOrGetFederalState($array['federal_state']);
        $universityTypeId = $this->insertOrGetUniversityType($array['university_type']);

        $sql = "
            UPDATE universities
            SET
            `short_title` = '{$array['short_title']}',
            `full_title` = '{$array['full_title']}',
            `city_id` = '{$cityId}',
            `federal_state_id` = '{$federalStateId}',
            `founded_year` = '{$array['founded_year']}',
            `university_type_id` = '{$universityTypeId}',
            `website` = '{$array['website']}',
            `studienberatung_address` = '{$array['studienberatung_address']}',
            `studienberatung_phone` = '{$array['studienberatung_phone']}',
            `studienberatung_fax` '{$array['studienberatung_fax']}',
            `studienberatung_website` = '{$array['studienberatung_website']}',
            `studienberatung_email` = '{$array['studienberatung_email']}',
            `studienberatung_notes` = '{$array['studienberatung_notes']}',
            `studierendensekretariat_address` = '{$array['studierendensekretariat_address']}',
            `studierendensekretariat_phone` = '{$array['studierendensekretariat_phone']}',
            `studierendensekretariat_website` = '{$array['studierendensekretariat_website']}',
            `studierendensekretariat_email` = '{$array['studierendensekretariat_email']}',
            `studierendenvertretung_name` = '{$array['studierendenvertretung_name']}',
            `studierendenvertretung_website` = '{$array['studierendenvertretung_website']}',
            `studierendenvertretung_email` = '{$array['studierendenvertretung_email']}'
            
            WHERE id='{$id}'
        ";

        return (bool)$this->query($sql);
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    public function insertUniversity(array $array): bool
    {
        //Checking does this university exist
        $universityId = $this->getIdByParameter('universities', [
            'short_title' => $array['short_title'],
            'full_title' => $array['full_title']
        ]);

        if ($universityId > 0) {
            return $this->updateUniversity($universityId, $array);
        }

        $array = $this->secure_check($array);

        $cityId = $this->insertOrGetCity($array['city']);
        $federalStateId = $this->insertOrGetFederalState($array['federal_state']);
        $universityTypeId = $this->insertOrGetUniversityType($array['university_type']);

        $sql = "
            INSERT INTO universities
            (
              `short_title`,
              `full_title`,
              `image3`,
              `city_id`,
              `federal_state_id`,
              `founded_year`,
              `university_type_id`,
              `website`,
              `studienberatung_address`,
              `studienberatung_phone`,
              `studienberatung_fax`,
              `studienberatung_website`,
              `studienberatung_email`,
              `studienberatung_notes`,
              `studierendensekretariat_address`,
              `studierendensekretariat_phone`,
              `studierendensekretariat_website`,
              `studierendensekretariat_email`,
              `studierendenvertretung_name`,
              `studierendenvertretung_website`,
              `studierendenvertretung_email`
            )
            VALUES
            (
              '{$array['short_title']}',
              '{$array['full_title']}',
              '{$array['logo']}',
              '{$cityId}',
              '{$federalStateId}',
              '{$array['founded_year']}',
              '{$universityTypeId}',
              '{$array['website']}',
              '{$array['studienberatung_address']}',
              '{$array['studienberatung_phone']}',
              '{$array['studienberatung_fax']}',
              '{$array['studienberatung_website']}',
              '{$array['studienberatung_email']}',
              '{$array['studienberatung_notes']}',
              '{$array['studierendensekretariat_address']}',
              '{$array['studierendensekretariat_phone']}',
              '{$array['studierendensekretariat_website']}',
              '{$array['studierendensekretariat_email']}',
              '{$array['studierendenvertretung_name']}',
              '{$array['studierendenvertretung_website']}',
              '{$array['studierendenvertretung_email']}'
            )
        ";

        return (bool)$this->query($sql);
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    public function insertJob(array $array): bool
    {
        foreach ($array['titles'] as $key => $title) {
            $title = $this->secure_check_string($title);
            $city = $this->secure_check_string($array['cities'][$key]);
            $company = $this->secure_check_string($array['companies'][$key]);
            $logo = $this->secure_check_string($array['logos'][$key]);
            $post_date = new DateTime($array['post_dates'][$key]);
            $post_date = date_format($post_date, 'Y-m-d H:i:s');

            $link = $this->secure_check_string($array['links'][$key]);

            $link = 'https://www.stepstone.de' . $link;

            $sql = "INSERT INTO `jobs` (`title`, `city`, `company`, `post_date`, `image1`, `link`) VALUES ('{$title}', '{$city}', '{$company}', '{$post_date}', '{$logo}', '{$link}')";
            $insertQuery = $this->query($sql);
            if (!$insertQuery) {
                echo "Error while adding a job!";
                echo '<br />';
                echo $sql;
                exit;
            }
        }

        return (bool)$insertQuery;
    }

    /**
     * @param string $city
     *
     * @return array
     */
    private function extractPostalFromCity(string $city): array
    {
        $re = '/(\d+) ([^,]+)/m';

        preg_match_all($re, $city, $matches, PREG_SET_ORDER, 0);

        $result = [];

        $result['city'] = $matches[0][2];
        $result['postal'] = $matches[0][1];

        return $result;
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    public function insertDormitory(array $array): bool
    {
        foreach ($array['titles'] as $key => $title) {
            $title = $this->secure_check_string($title);
            $link = $this->secure_check_string($array['links'][$key]);
            $address = $this->secure_check_string($array['addresses'][$key]);
            $city = $this->secure_check_string($array['cities'][$key]);

            $postal = trim($this->extractPostalFromCity($city)['postal']);
            $city = trim($this->extractPostalFromCity($city)['city']);

            $places = $this->secure_check_string($array['places'][$key]);
            $price = $this->secure_check_string($array['prices'][$key]);

            $sql = "INSERT INTO `dormitories` (`title`, `link`, `address`, `city`, `postal`, `places`, `price`) VALUES ('{$title}', '{$link}', '{$address}', '{$city}', '{$postal}', '{$places}', '{$price}')";
            $insertQuery = $this->query($sql);
            if (!$insertQuery) {
                echo "Error while adding a dormitory!";
                echo '<br />';
                echo $sql;
                exit;
            }
        }

        return (bool)$insertQuery;
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    public function insertFsj(array $array): bool
    {
        foreach ($array['titles'] as $key => $title) {
            $title = $this->secure_check_string($title);
            $link = $this->secure_check_string($array['links'][$key]);
            $city = $this->secure_check_string($array['cities'][$key]);
            $organisation = $this->secure_check_string($array['organisations'][$key]);
            $city = $this->secure_check_string(trim($this->extractPostalFromCity($city)['city']));
            $image = $this->secure_check_string($array['logos'][$key]);

            $sql = "INSERT INTO `external_fsj` (`title`, `link`, `organisation`, `city`, `image1`) VALUES ('{$title}', '{$link}', '{$organisation}', '{$city}', '{$image}')";
            $insertQuery = $this->query($sql);
            if (!$insertQuery) {
                echo "Error while adding an FSJ!";
                echo '<br />';
                echo $sql;
                exit;
            }
        }

        return (bool)$insertQuery;
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    public function insertExternalAusbildung(array $array): bool
    {
        foreach ($array['titles'] as $key => $title) {
            $title = $this->secure_check_string($title);
            $begin = $this->secure_check_string($array['begins'][$key]);
            $city = $this->secure_check_string($array['cities'][$key]);
            $company = $this->secure_check_string($array['companies'][$key]);
            $link = $this->secure_check_string($array['links'][$key]);
            $logo = $this->secure_check_string($array['logos'][$key]);
            $image = $this->secure_check_string($array['images'][$key]);

            $sql = "INSERT INTO `ausbildung_external` (`title`, `begin`, `city`, `company`, `link`, `image1`, `image2`) VALUES ('{$title}', '{$begin}', '{$city}', '{$company}', '{$link}', '{$logo}', '{$image}')";
            $insertQuery = $this->query($sql);
            if (!$insertQuery) {
                echo "Error while adding ausbildung item!";
                echo '<br />';
                echo $sql;
                $this->handleParseError('ausbildung_external', 'Error while adding ausbildung item!' . $sql);
                exit;
            }
        }

        return (bool)$insertQuery;
    }

    /**
     * @param string $universityShortTitle
     * @param string $universityFullTitle
     * @param array $faculties
     *
     * @return bool
     */
    public function insertFaculty(string $universityShortTitle, string $universityFullTitle, array $faculties): bool
    {
        $universityId = $this->getIdByParameter('universities',
            ['short_title' => $universityShortTitle, 'full_title' => $universityFullTitle]);

        $this->deleteFaculties($universityId);

        if ($universityId === 0) {
            echo "University {$universityFullTitle} not found!";
            echo '<br />';
            echo 'short title:' . $universityShortTitle . '<br />' . 'full title:' . $universityFullTitle;

            return false;
        }

        foreach ($faculties as $key => $value) {
            $degreeId = $this->insertOrGetDegree($value['degree']);
            $semester = $this->insertOrGetSemester($value['semester']);

            $sql = "
            INSERT INTO faculties 
            (
              `title`,
              `university_id`,
              `semester_id`,
              `degree_id`
            )
            VALUES
            (
              '{$value['title']}',
              '{$universityId}',
              '{$semester}',
              '{$degreeId}'
            )
            ";

            $query = $this->query($sql);

            if (!$query) {

                echo "One of queries failed...";

                echo $sql;

                $this->deleteFaculties($universityId);

                return false;
            }
        }

        return true;
    }

    /**
     * @param int $universityId
     *
     * @return int
     */
    public function deleteFaculties(int $universityId): int
    {
        $sql = "DELETE FROM faculties WHERE university_id='{$universityId}'";

        return (bool)$this->query($sql);
    }

    /**
     * @param int $universityId
     *
     * @return int
     */
    public function deleteZulassungsfreis(int $universityId): int
    {
        $sql = "DELETE FROM zulassungsfreis WHERE university_id='{$universityId}'";

        return (bool)$this->query($sql);
    }

    /**
     * @param string $universityShortTitle
     * @param string $universityFullTitle
     * @param array $zulassungsfreiList
     *
     * @return bool
     */
    public function insertZulassungsFrei(
        string $universityShortTitle,
        string $universityFullTitle,
        array $zulassungsfreiList
    ): bool {
        $universityId = $this->getIdByParameter('universities',
            ['short_title' => $universityShortTitle, 'full_title' => $universityFullTitle]);

        $this->deleteZulassungsfreis($universityId);

        if ($universityId === 0) {
            echo "University {$universityFullTitle} not found!";

            return false;
        }

        foreach ($zulassungsfreiList as $key => $value) {
            $degreeId = $this->insertOrGetDegree($value['degree']);
            $semesterId = $this->insertOrGetSemester($value['semester']);

            $facultyId = $this->getIdByParameter('faculties', [
                'degree_id' => $degreeId,
                'university_id' => $universityId,
                'title' => $value['title']
            ], true);

            $sql = "UPDATE faculties SET zulassungsfrei=1, deadline_date = '{$value['deadline_date']}' WHERE id='{$facultyId}'";

            $query = $this->query($sql);

            if (!$query) {

                echo "One of queries failed...";

                echo $sql;

                $this->deleteZulassungsfreis($universityId);

                return false;
            }
        }

        return true;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    function get_http_response_code(string $url): string
    {
        $headers = get_headers($url);

        return substr($headers[0], 9, 3);
    }
}
