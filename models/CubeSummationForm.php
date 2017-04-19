<?php

namespace app\models;

use Yii;
use yii\base\Model;

class CubeSummationForm extends Model
{

    // CONST LIMIT CONSTRAINTS
    const MIN_VALUE_TEST_CASES = 1, MAX_VALUE_TEST_CASES = 50,  MIN_VALUE_DIMENSION = 1, MAX_VALUE_DIMENSION= 100;

    private $cube;

    public $cases = [];

    private $testCases;

    public $input;

    public $problem;

    public function rules()
    {
        return [['problem', 'required']];
    }

    /**
     * @return null|string
     */
    public function validateInput()
    {
        $this->problem = $this->removeSpaces($this->problem);
        $this->input = explode("\n", $this->problem);
        $this->testCases = trim($this->input[0]);
        if (!$this->validateTestCases()) {
            $this->addErrorForTestCases();
            return false;
        }
        $this->createCases();
        if (!$this->reviewEachCase()) {
            return false;
        }

        if (count($this->cases)!= $this->testCases) {
            $this->addErrorForTestCasesSended();
            return false;
        }
        return true;
    }

    private function createCases()
    {
        $problem = 0;
        for ($i = 1; $i < count($this->input); $i++) {
            $this->input[$i] = $this->removeSpaces($this->input[$i]);
            $case = explode(" ", $this->input[$i]);
            if ($this->validateOperations($case[0])=== false) {
                array_push($this->cases, ["test" => $this->input[$i], "problems" => [], "solutions" => [] ]);
                $problem +=1 ;
            }
            else {
                array_push($this->cases[$problem-1]["problems"], $this->input[$i]);
            }
        }
    }

    /**
     * @param $content
     * @return string
     */
    private function removeSpaces($content)
    {
        return rtrim(ltrim($content));
    }


    /**
     * @return bool
     */
    private function reviewEachCase()
    {
        foreach ($this->cases as $case) {
            $operations = explode(" ", $case['test']);
            if ($operations[1] != count($case["problems"])) {
                $this->addErrorForTestCaseFormed();
                return false;
            }
            if (!$this->validateDimensionsForCase($operations[0])) {
                $this->addErrorForInvalidDimensionInCase();
                return false;
            }
        }
        return true;
    }

    /**
     * @param $dimension
     * @return bool
     */
    private function validateDimensionsForCase($dimension)
    {
        return $dimension >= self::MIN_VALUE_DIMENSION && $dimension <= self::MAX_VALUE_DIMENSION;
    }

    public function resolve()
    {
        for ($i=0; $i<count($this->cases) ;$i++){
            $case = $this->cases[$i];
            $this->fillCubeWithZeros( explode(" ", $case['test'] )[0]);
            $solutions = $this->searchSolutionForCase($case);
            $this->cases[$i]["solutions"] = $solutions;
        }
    }

    /**
     * @return bool
     */
    private function validateTestCases()
    {
        return $this->testCases >= self::MIN_VALUE_TEST_CASES && $this->testCases <= self::MAX_VALUE_TEST_CASES;
    }

    /**
     * @param $case
     * @return array
     */
    private function searchSolutionForCase($case)
    {
        $solutions = [];
        foreach ($case["problems"] as $problem){
            $sentence = explode(" ", $problem);
            $resultSet = $this->runSentence($sentence);
            if ($resultSet != -1) {
                array_push($solutions,$resultSet);
            }
        }
        return $solutions;
    }

    /**
     * @param $sentence
     * @return int
     */
    private function runSentence($sentence)
    {
        $instruction = $sentence[0];
        if ($instruction === 'UPDATE') {
            $this->update($sentence[1], $sentence[2], $sentence[3], $sentence[4]);
            return -1; // value updated
        }
        if ($instruction === 'QUERY') {
            return $this->query($sentence[1], $sentence[2], $sentence[3], $sentence[4], $sentence[5], $sentence[6]);
        }
    }

    /**
     * @param $value
     * @return mixed
     */
    private function indexInCube($value){
        return $value-1;
    }

    /**
     * @param $xi
     * @param $yi
     * @param $zi
     * @param $value
     */
    private function update($xi, $yi, $zi, $value){
        $this->cube[$this->indexInCube($xi)][$this->indexInCube($yi)][$this->indexInCube($zi)] = $value;
    }

    /**
     * @param $xi
     * @param $yi
     * @param $zi
     * @param $xj
     * @param $yj
     * @param $zj
     * @return int
     */
    private function query($xi, $yi, $zi, $xj, $yj,$zj ){
        $xi= $this->indexInCube($xi);
        $yi= $this->indexInCube($yi);
        $zi= $this->indexInCube($zi);
        $xj= $this->indexInCube($xj);
        $yj= $this->indexInCube($yj);
        $zj= $this->indexInCube($zj);
        // ranges to sum formula : sum(x2+1,y2+1,z2+1) - sum(x1,y1,z1) - sum(x1,y2+1,z2+1) - sum(x2+1,y1,z2+1) - sum(x2+1,y2+1,z1) + sum(x1,y1,z2+1) + sum(x1,y2+1,z1) + sum(x2+1,y1,z1) = ∑(x1, y1, z1) to (x2, y2, z2)
        $first = $this->sum($xj+1,$yj+1,$zj+1);
        $second = $this->sum($xi,$yi,$zi);
        $third = $this->sum($xi,$yj+1,$zj+1);
        $four = $this->sum($xj+1,$yi,$zj+1);
        $five = $this->sum($xj+1,$yj+1,$zi);
        $six = $this->sum($xi,$yi,$zj+1);
        $seven = $this->sum($xi,$yj+1,$zi);
        $eight = $this->sum($xj+1,$yi,$zi);
        $sum = $first - $second - $third - $four - $five + $six + $seven + $eight;
        return $sum;
    }


    /**
     * @param $x
     * @param $y
     * @param $z
     * @return int
     */
    private function sum($x, $y, $z)
    {
        $sum = 0;
        for($i = 0; $i < $x; $i++) {
            for($j = 0; $j < $y; $j++) {
                for($k = 0; $k < $z; $k++) {
                    $sum += $this->cube[$i][$j][$k];
                }
            }
        }
        return $sum;
    }

    /**
     * @param $operation
     * @return bool
     */
    private function validateOperations($operation)
    {
        return $operation === 'UPDATE' || $operation  === 'QUERY';
    }

    /**
     * @param $dimension
     */
    private function fillCubeWithZeros($dimension)
    {
        for($i =0; $i<$dimension; $i++) {
            for($j =0; $j<$dimension; $j++) {
                for($k =0; $k<$dimension; $k++) {
                    $this->cube[$i][$j][$k] = 0;
                }
            }
        }
    }

    private function addErrorForTestCases()
    {
        $this->addError('problem', 'the test cases should be between: '. self::MIN_VALUE_TEST_CASES .' to ' . self::MAX_VALUE_TEST_CASES);
    }

    private function addErrorForTestCasesSended()
    {
        $this->addError('problem', 'the units test cases are different that the sended');
    }

    private function addErrorForInvalidDimensionInCase()
    {
        $this->addError('problem', 'the dimension for each test should be between: '. self::MIN_VALUE_DIMENSION .' to ' . self::MAX_VALUE_DIMENSION);
    }

    private function addErrorForTestCaseFormed()
    {
        $this->addError('problem', 'the test cases are bad created');
    }
}
