<?php

namespace Rubix\ML\NeuralNet\Optimizers;

use MathPHP\LinearAlgebra\Matrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use Rubix\ML\NeuralNet\Layers\Parametric;
use InvalidArgumentException;
use SplObjectStorage;

class AdaGrad implements Optimizer
{
    /**
     * The learning rate. i.e. the master step size.
     *
     * @var float
     */
    protected $rate;

    /**
     * The sum of squared gradient matrices for each layer.
     *
     * @var \SplObjectStorage
     */
    protected $cache;

    /**
     * @param  float  $rate
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(float $rate = 0.001)
    {
        if ($rate <= 0.0) {
            throw new InvalidArgumentException('The learning rate must be set'
                . ' to a positive value.');
        }

        $this->rate = $rate;
        $this->cache = new SplObjectStorage();
    }

    /**
     * Initialize the optimizer for a particular layer.
     *
     * @param  \Rubix\ML\NeuralNet\Layers\Parametric  $layer
     * @return void
     */
    public function initialize(Parametric $layer) : void
    {
        $this->cache->attach($layer, MatrixFactory::zero($layer->weights()
            ->getM(), $layer->weights()->getN()));
    }

    /**
     * Calculate the step for a parametric layer and return the magnitude.
     *
     * @param  \Rubix\ML\NeuralNet\Layers\Parametric  $layer
     * @return float
     */
    public function step(Parametric $layer) : float
    {
        $cache = $this->cache[$layer]->add($layer->gradients()
            ->hadamardProduct($layer->gradients()));

        $steps = [];

        foreach ($layer->gradients()->getMatrix() as $i => $row) {
            foreach ($row as $j => $column) {
                $steps[$i][$j] = $this->rate * $column
                    / (sqrt($cache[$i][$j]) + self::EPSILON);
            }
        }

        $this->cache[$layer] = $cache;

        $steps = new Matrix($steps);

        $layer->update($steps);

        return $steps->oneNorm();
    }
}
